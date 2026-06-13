<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;
use App\Repositories\{LiquiditySessionRepository,LiquidityTeamRepository,LiquidityPoolRepository,LiquidityRoundRepository,LiquidityActionRepository,LiquidityEventRepository};
use DomainException;
use PDO;

final class LiquidityPoolService {
    private PDO $pdo; private LiquiditySessionRepository $sessions; private LiquidityTeamRepository $teams; private LiquidityPoolRepository $pool; private LiquidityRoundRepository $rounds; private LiquidityActionRepository $actions; private LiquidityEventRepository $events;
    private const ACTIONS=['deposit_nft','withdraw_nft_btc','withdraw_nft_cash','buy_btc','sell_btc','buy_nft','sell_nft','buy_share','sell_share','trade_nft_between_teams','pass'];
    public function __construct(?PDO $pdo=null){$this->pdo=$pdo??Database::connection();$this->sessions=new LiquiditySessionRepository($this->pdo);$this->teams=new LiquidityTeamRepository($this->pdo);$this->pool=new LiquidityPoolRepository($this->pdo);$this->rounds=new LiquidityRoundRepository($this->pdo);$this->actions=new LiquidityActionRepository($this->pdo);$this->events=new LiquidityEventRepository($this->pdo);}
    public function createSession($d,$by=null):array{$d['access_code']=$d['access_code']??strtoupper(substr(bin2hex(random_bytes(4)),0,8));$id=$this->sessions->create($d,$by);$this->pool->createInitialState($id);$this->rounds->createRound($id,1);$this->sessions->setStatus($id,'active');return $this->sessions->findById($id)??[];}
    public function createTeam($sid,$name):array{$s=$this->mustSession((int)$sid);$id=$this->teams->create(['session_id'=>$s['id'],'name'=>$name,'login_code'=>strtoupper(substr(bin2hex(random_bytes(3)),0,6)),'cash_balance'=>$s['initial_cash'],'nft_balance'=>$s['initial_nfts']]);return $this->teams->findById($id)??[];}
    public function loginTeam($access,$login):array{$s=$this->sessions->findByAccessCode($access);if(!$s)throw new DomainException('Sessão não encontrada.');$t=$this->teams->findByLoginCodeAndSession($login,(int)$s['id']);if(!$t)throw new DomainException('Equipe não encontrada.');return ['session'=>$s,'team'=>$t];}
    private function mustSession(int $id):array{$s=$this->sessions->findById($id);if(!$s)throw new DomainException('Sessão não encontrada.');return $s;}
    private function isFinalClosed(array $s):bool{return ($s['session_phase']??'')==='final_closed'||($s['session_phase']??'')==='closed'||($s['status']??'')==='closed';}

    public function submitTeamAction($sid,$tid,$a,$q=1,$extra=[]):void{
        $sid=(int)$sid;
        $tid=(int)$tid;
        $q=$q===null?null:(float)$q;

        if(!in_array($a,self::ACTIONS,true))throw new DomainException('Ação inválida.');

        $s=$this->mustSession($sid);
        if($this->isFinalClosed($s)) throw new DomainException('A final já foi encerrada. Nenhuma nova ação pode ser registrada.');
        if(($s['status']??'')!=='active') throw new DomainException('Sessão não está ativa.');

        $t=$this->teams->findById($tid);
        $p=$this->pool->findBySessionId($sid);
        if(!$t||!$p||(int)$t['session_id']!==$sid) throw new DomainException('Equipe inválida.');
        if((int)($t['is_eliminated']??0)===1) throw new DomainException('Este time foi eliminado na semifinal e não pode mais realizar ações.');

        $phase=(string)($s['session_phase']??'regular');
        if(!in_array($phase,['regular','semifinal','semifinal_evaluated','final'],true)) throw new DomainException('Fase encerrada.');

        $r=(int)$s['current_round'];
        if(!$this->rounds->hasOpenRound($sid,$r)) throw new DomainException('Rodada encerrada. Aguarde o professor iniciar a próxima rodada.');
        if($this->actions->hasTeamActed($sid,$r,$tid)) throw new DomainException('Este time já usou sua ação nesta rodada.');

        $this->pdo->beginTransaction();
        try {
            $result=['cash'=>0.0,'btc'=>0.0,'nft'=>0,'share'=>0,'poolN'=>(int)$p['pool_nfts'],'tot'=>(int)$p['total_shares'],'msg'=>'Você passou a vez.'];
            if($a==='deposit_nft')$result=$this->depositNft($t,$s,$p,(int)($q??1));
            elseif($a==='withdraw_nft_btc')$result=$this->withdrawNftWithBtc($t,$s,$p,(int)($q??1));
            elseif($a==='withdraw_nft_cash')$result=$this->withdrawNftWithCash($t,$s,$p,(int)($q??1));
            elseif($a==='buy_btc')$result=$this->buyBtc($sid,$tid,$t,$q,$extra,$p);
            elseif($a==='sell_btc')$result=$this->sellBtc($sid,$tid,$t,$q,$extra,$p);
            elseif($a==='buy_nft'||$a==='trade_nft_between_teams')$result=$this->buyNft($sid,$tid,$t,$q,$extra,$p);
            elseif($a==='sell_nft')$result=$this->sellNft($sid,$tid,$t,$q,$extra,$p);
            elseif($a==='buy_share')$result=$this->buyShare($sid,$tid,$t,$q,$extra,$p);
            elseif($a==='sell_share')$result=$this->sellShare($sid,$tid,$t,$q,$extra,$p);
            else $result=$this->passTurn($t,$p);

            $this->teams->updateBalances($tid,(float)$t['cash_balance']+$result['cash'],(float)$t['btc_balance']+$result['btc'],(int)$t['nft_balance']+$result['nft'],(int)$t['pool_shares']+$result['share']);
            $this->pool->updateState($sid,$result['poolN'],$result['tot'],(float)$p['total_value'],(float)$p['yield_per_share'],$this->determinePoolStatus($result['poolN'],$result['tot']));
            $this->actions->create($sid,$r,$tid,$a,$q??1.0,$extra['target_team_id']??null,$extra['price']??null);
            $this->events->create($sid,$r,$tid,$a,$result['msg'],$result['cash'],$result['btc'],$result['nft'],$result['share']);
            $this->updatePoolTotals($sid);
            $this->pdo->commit();
        } catch(\Throwable $e){
            if($this->pdo->inTransaction())$this->pdo->rollBack();
            throw $e;
        }
    }
    public function depositNft($t,$s,$p,$qty=1):array{$qty=(int)$qty;if($qty<1)throw new DomainException('Quantidade inválida.');if((int)$t['nft_balance']<$qty)throw new DomainException('Este time não possui NFT em mãos para depositar.');return ['cash'=>0,'btc'=>(float)$s['btc_deposit_reward']*$qty,'nft'=>-$qty,'share'=>$qty,'poolN'=>(int)$p['pool_nfts']+$qty,'tot'=>(int)$p['total_shares']+$qty,'msg'=>'Time '.$t['name'].' depositou '.$qty.' NFT(s) na piscina, recebeu '.rtrim(rtrim(number_format((float)$s['btc_deposit_reward']*$qty,2,'.',''),'0'),'.').' BTC e ganhou '.$qty.' cota(s).'];}
    public function withdrawNftWithBtc($t,$s,$p,$qty=1):array{$qty=(int)$qty;if($qty<1)throw new DomainException('Quantidade inválida.');if((int)$t['pool_shares']<$qty)throw new DomainException('Este time não possui cota para retirar NFT.');if((float)$t['btc_balance']<((float)$s['btc_withdraw_cost']*$qty))throw new DomainException('Saldo de BTC insuficiente.');if((int)$p['pool_nfts']<$qty)throw new DomainException('A piscina não possui NFT disponível para retirada.');return ['cash'=>0,'btc'=>-((float)$s['btc_withdraw_cost']*$qty),'nft'=>$qty,'share'=>-$qty,'poolN'=>(int)$p['pool_nfts']-$qty,'tot'=>(int)$p['total_shares']-$qty,'msg'=>'Time '.$t['name'].' retirou '.$qty.' NFT(s) da piscina pagando '.rtrim(rtrim(number_format((float)$s['btc_withdraw_cost']*$qty,2,'.',''),'0'),'.').' BTC.'];}
    public function withdrawNftWithCash($t,$s,$p,$qty=1):array{$qty=(int)$qty;if($qty<1)throw new DomainException('Quantidade inválida.');if((int)$t['pool_shares']<$qty)throw new DomainException('Este time não possui cota para retirar NFT.');if((float)$t['cash_balance']<((float)$s['cash_withdraw_cost']*$qty))throw new DomainException('Caixa insuficiente.');if((int)$p['pool_nfts']<$qty)throw new DomainException('A piscina não possui NFT disponível para retirada.');return ['cash'=>-((float)$s['cash_withdraw_cost']*$qty),'btc'=>0,'nft'=>$qty,'share'=>-$qty,'poolN'=>(int)$p['pool_nfts']-$qty,'tot'=>(int)$p['total_shares']-$qty,'msg'=>'Time '.$t['name'].' retirou '.$qty.' NFT(s) da piscina pagando R$ '.number_format((float)$s['cash_withdraw_cost']*$qty,2,',','.').'.'];}
    private function formatAmount(float $value):string{return rtrim(rtrim(number_format($value,2,'.',''),'0'),'.');}
    private function formatMoney(float $value):string{return 'R$ '.number_format($value,2,',','.');}
    private function marketInputs($sid,$tid,$q,$extra):array{if($q===null)throw new DomainException('Quantidade inválida.');$q=(float)$q;if($q<=0)throw new DomainException('Quantidade inválida.');$target=(int)($extra['target_team_id']??0);if($target<=0)throw new DomainException('Time alvo obrigatório.');if($target===$tid)throw new DomainException('Time principal e time alvo não podem ser o mesmo.');if(!array_key_exists('price',$extra)||$extra['price']===null)throw new DomainException('Preço inválido.');$price=(float)$extra['price'];if($price<=0)throw new DomainException('Preço inválido.');$targetTeam=$this->teams->findById($target);if(!$targetTeam||(int)$targetTeam['session_id']!==(int)$sid)throw new DomainException('Time alvo inválido.');if((int)($targetTeam['is_eliminated']??0)===1)throw new DomainException('Time alvo eliminado não pode negociar.');return [$targetTeam,$q,$price,$q*$price];}
    private function updateTeam(array $team,float $cash,float $btc,int $nft,int $share):void{$this->teams->updateBalances((int)$team['id'],(float)$team['cash_balance']+$cash,(float)$team['btc_balance']+$btc,(int)$team['nft_balance']+$nft,(int)$team['pool_shares']+$share);}
    public function buyBtc($sid,$tid,$buyer,$q,$extra,$p):array{[$seller,$q,$price,$total]=$this->marketInputs($sid,$tid,$q,$extra);if((float)$buyer['cash_balance']<$total)throw new DomainException('Caixa insuficiente.');if((float)$seller['btc_balance']<$q)throw new DomainException('BTC insuficiente.');$this->updateTeam($seller,$total,-$q,0,0);return ['cash'=>-$total,'btc'=>$q,'nft'=>0,'share'=>0,'poolN'=>(int)$p['pool_nfts'],'tot'=>(int)$p['total_shares'],'msg'=>'Time '.$buyer['name'].' comprou '.$this->formatAmount($q).' BTC do Time '.$seller['name'].' por '.$this->formatMoney($total).'.'];}
    public function sellBtc($sid,$tid,$seller,$q,$extra,$p):array{[$buyer,$q,$price,$total]=$this->marketInputs($sid,$tid,$q,$extra);if((float)$seller['btc_balance']<$q)throw new DomainException('BTC insuficiente.');if((float)$buyer['cash_balance']<$total)throw new DomainException('Caixa insuficiente.');$this->updateTeam($buyer,-$total,$q,0,0);return ['cash'=>$total,'btc'=>-$q,'nft'=>0,'share'=>0,'poolN'=>(int)$p['pool_nfts'],'tot'=>(int)$p['total_shares'],'msg'=>'Time '.$seller['name'].' vendeu '.$this->formatAmount($q).' BTC para o Time '.$buyer['name'].' por '.$this->formatMoney($total).'.'];}
    public function buyNft($sid,$tid,$buyer,$q,$extra,$p):array{[$seller,$q,$price,$total]=$this->marketInputs($sid,$tid,$q,$extra);$qty=(int)$q;if((float)$q!== (float)$qty)throw new DomainException('Quantidade inválida.');if((float)$buyer['cash_balance']<$total)throw new DomainException('Caixa insuficiente.');if((int)$seller['nft_balance']<$qty)throw new DomainException('NFT insuficiente.');$this->updateTeam($seller,$total,0,-$qty,0);return ['cash'=>-$total,'btc'=>0,'nft'=>$qty,'share'=>0,'poolN'=>(int)$p['pool_nfts'],'tot'=>(int)$p['total_shares'],'msg'=>'Time '.$buyer['name'].' comprou '.$qty.' NFT em mãos do Time '.$seller['name'].' por '.$this->formatMoney($total).'.'];}
    public function sellNft($sid,$tid,$seller,$q,$extra,$p):array{[$buyer,$q,$price,$total]=$this->marketInputs($sid,$tid,$q,$extra);$qty=(int)$q;if((float)$q!== (float)$qty)throw new DomainException('Quantidade inválida.');if((int)$seller['nft_balance']<$qty)throw new DomainException('NFT insuficiente.');if((float)$buyer['cash_balance']<$total)throw new DomainException('Caixa insuficiente.');$this->updateTeam($buyer,-$total,0,$qty,0);return ['cash'=>$total,'btc'=>0,'nft'=>-$qty,'share'=>0,'poolN'=>(int)$p['pool_nfts'],'tot'=>(int)$p['total_shares'],'msg'=>'Time '.$seller['name'].' vendeu '.$qty.' NFT em mãos para o Time '.$buyer['name'].' por '.$this->formatMoney($total).'.'];}
    public function buyShare($sid,$tid,$buyer,$q,$extra,$p):array{[$seller,$q,$price,$total]=$this->marketInputs($sid,$tid,$q,$extra);$qty=(int)$q;if((float)$q!== (float)$qty)throw new DomainException('Quantidade inválida.');if((float)$buyer['cash_balance']<$total)throw new DomainException('Caixa insuficiente.');if((int)$seller['pool_shares']<$qty)throw new DomainException('Cota insuficiente.');$this->updateTeam($seller,$total,0,0,-$qty);return ['cash'=>-$total,'btc'=>0,'nft'=>0,'share'=>$qty,'poolN'=>(int)$p['pool_nfts'],'tot'=>(int)$p['total_shares'],'msg'=>'Time '.$buyer['name'].' comprou '.$qty.' cota da piscina do Time '.$seller['name'].' por '.$this->formatMoney($total).'.'];}
    public function sellShare($sid,$tid,$seller,$q,$extra,$p):array{[$buyer,$q,$price,$total]=$this->marketInputs($sid,$tid,$q,$extra);$qty=(int)$q;if((float)$q!== (float)$qty)throw new DomainException('Quantidade inválida.');if((int)$seller['pool_shares']<$qty)throw new DomainException('Cota insuficiente.');if((float)$buyer['cash_balance']<$total)throw new DomainException('Caixa insuficiente.');$this->updateTeam($buyer,-$total,0,0,$qty);return ['cash'=>$total,'btc'=>0,'nft'=>0,'share'=>-$qty,'poolN'=>(int)$p['pool_nfts'],'tot'=>(int)$p['total_shares'],'msg'=>'Time '.$seller['name'].' vendeu '.$qty.' cota da piscina para o Time '.$buyer['name'].' por '.$this->formatMoney($total).'.'];}
    public function passTurn($t,$p):array{return ['cash'=>0,'btc'=>0,'nft'=>0,'share'=>0,'poolN'=>(int)$p['pool_nfts'],'tot'=>(int)$p['total_shares'],'msg'=>'Time '.$t['name'].' passou a vez.'];}


    public function createTradeProposal(int $sid,int $proposerTeamId,int $counterpartyTeamId,int $userId,string $actionType,string $assetType,float $quantity,float $unitPrice,bool $master=false): int{
        if(!in_array($actionType,['buy','sell'],true)||!in_array($assetType,['btc','nft','share'],true))throw new DomainException('Transação inválida.');
        if($quantity<=0||$unitPrice<=0)throw new DomainException('Quantidade e preço devem ser positivos.');
        if($proposerTeamId===$counterpartyTeamId)throw new DomainException('Escolha uma equipe contraparte diferente.');
        $s=$this->mustSession($sid); $round=(int)$s['current_round'];
        if(!$master && $this->actions->hasTeamActed($sid,$round,$proposerTeamId))throw new DomainException('Sua equipe já realizou uma ação nesta rodada.');
        $prop=$this->teams->findById($proposerTeamId); $counter=$this->teams->findById($counterpartyTeamId);
        if(!$prop||!$counter||(int)$prop['session_id']!==$sid||(int)$counter['session_id']!==$sid)throw new DomainException('Equipe inválida.');
        $total=$quantity*$unitPrice;
        $this->validateTradeResources($prop,$counter,$actionType,$assetType,$quantity,$total);
        $this->pdo->beginTransaction();
        try{
            $repo=new \App\Repositories\LiquidityTradeProposalRepository($this->pdo);
            $id=$repo->create(['game_id'=>$sid,'round_number'=>$round,'proposer_team_id'=>$proposerTeamId,'counterparty_team_id'=>$counterpartyTeamId,'proposer_user_id'=>$userId,'action_type'=>$actionType,'asset_type'=>$assetType,'quantity'=>$quantity,'unit_price'=>$unitPrice,'total_price'=>$total,'status'=>$master?'master_executed':'pending_counterparty','master_approved_at'=>$master?date('Y-m-d H:i:s'):null,'executed_at'=>$master?date('Y-m-d H:i:s'):null]);
            if($master){$proposal=$repo->findInGame($sid,$id); $this->executeTradeBalances($proposal,true);}
            else{$this->actions->create($sid,$round,$proposerTeamId,'market_proposal',1.0,$counterpartyTeamId,$unitPrice);$this->events->create($sid,$round,$proposerTeamId,'trade_proposed','Equipe '.$prop['name'].' propôs '.($actionType==='buy'?'comprar':'vender').' '.$this->formatAmount($quantity).' '.$assetType.' '.($actionType==='buy'?'da':'para a').' Equipe '.$counter['name'].' por '.$this->formatMoney($unitPrice).' cada.',0,0,0,0);}
            $this->pdo->commit(); return $id;
        }catch(\Throwable $e){if($this->pdo->inTransaction())$this->pdo->rollBack();throw $e;}
    }
    public function approveTradeProposal(int $sid,int $proposalId):void{$repo=new \App\Repositories\LiquidityTradeProposalRepository($this->pdo);$p=$repo->findInGame($sid,$proposalId);if(!$p)throw new DomainException('Proposta não encontrada.');if($p['status']!=='pending_counterparty')throw new DomainException('Proposta não está pendente.');$this->pdo->beginTransaction();try{$this->executeTradeBalances($p,false);$this->pdo->prepare("UPDATE liquidity_trade_proposals SET status='executed', counterparty_approved_at=NOW(), executed_at=NOW(), updated_at=NOW() WHERE id=?")->execute([$proposalId]);$this->pdo->commit();}catch(\Throwable $e){if($this->pdo->inTransaction())$this->pdo->rollBack();throw $e;}}
    public function rejectTradeProposal(int $sid,int $proposalId):void{$repo=new \App\Repositories\LiquidityTradeProposalRepository($this->pdo);$p=$repo->findInGame($sid,$proposalId);if(!$p)throw new DomainException('Proposta não encontrada.');if($p['status']!=='pending_counterparty')throw new DomainException('Proposta não está pendente.');$prop=$this->teams->findById((int)$p['proposer_team_id']);$counter=$this->teams->findById((int)$p['counterparty_team_id']);$this->pdo->prepare("UPDATE liquidity_trade_proposals SET status='rejected', rejected_at=NOW(), updated_at=NOW() WHERE id=?")->execute([$proposalId]);$this->events->create($sid,(int)$p['round_number'],(int)$p['counterparty_team_id'],'trade_rejected','Equipe '.($counter['name']??'contraparte').' rejeitou a proposta enviada por Equipe '.($prop['name']??'proponente').'.',0,0,0,0);}
    private function validateTradeResources(array $prop,array $counter,string $action,string $asset,float $q,float $total):void{$buyer=$action==='buy'?$prop:$counter;$seller=$action==='buy'?$counter:$prop;if((float)$buyer['cash_balance']<$total)throw new DomainException('Caixa insuficiente para executar a transação.');if($asset==='btc'&&(float)$seller['btc_balance']<$q)throw new DomainException('BTC insuficiente para executar a transação.');if($asset==='nft'&&((int)$q!=$q||(int)$seller['nft_balance']<(int)$q))throw new DomainException('NFT insuficiente para executar a transação.');if($asset==='share'&&((int)$q!=$q||(int)$seller['pool_shares']<(int)$q))throw new DomainException('Cota insuficiente para executar a transação.');}
    private function executeTradeBalances(array $p,bool $master):void{$prop=$this->teams->findById((int)$p['proposer_team_id']);$counter=$this->teams->findById((int)$p['counterparty_team_id']);if(!$prop||!$counter)throw new DomainException('Equipe inválida.');$q=(float)$p['quantity'];$total=(float)$p['total_price'];$this->validateTradeResources($prop,$counter,(string)$p['action_type'],(string)$p['asset_type'],$q,$total);$buyer=$p['action_type']==='buy'?$prop:$counter;$seller=$p['action_type']==='buy'?$counter:$prop;$qtyInt=(int)$q;$this->updateTeam($buyer,-$total,$p['asset_type']==='btc'?$q:0,$p['asset_type']==='nft'?$qtyInt:0,$p['asset_type']==='share'?$qtyInt:0);$this->updateTeam($seller,$total,$p['asset_type']==='btc'?-$q:0,$p['asset_type']==='nft'?-$qtyInt:0,$p['asset_type']==='share'?-$qtyInt:0);$msg=($master?'Transação executada pelo Master Gold: ':'Equipe '.($counter['name']??'contraparte').' aprovou a proposta. ').'Equipe '.$seller['name'].' vendeu '.$this->formatAmount($q).' '.$p['asset_type'].' para Equipe '.$buyer['name'].' por '.$this->formatMoney((float)$p['unit_price']).' cada.';$this->events->create((int)$p['game_id'],(int)$p['round_number'],(int)$buyer['id'],$master?'trade_master_executed':'trade_executed',$msg,-$total,$p['asset_type']==='btc'?$q:0,$p['asset_type']==='nft'?$qtyInt:0,$p['asset_type']==='share'?$qtyInt:0);}

    public function advanceRound($sid):array{
        $sid=(int)$sid;
        $s=$this->mustSession($sid);
        if($this->isFinalClosed($s))throw new DomainException('A final já foi encerrada. A rodada não pode mais ser alterada.');
        if(($s['status']??'')==='closed')throw new DomainException('Sessão encerrada.');

        $round=(int)$s['current_round'];
        $this->pdo->beginTransaction();
        try{
            $roundState=$this->rounds->getCurrentRoundForUpdate($sid,$round);
            if(!$roundState){
                $this->rounds->createRound($sid,$round);
                $roundState=$this->rounds->getCurrentRoundForUpdate($sid,$round);
            }
            if(!$roundState||($roundState['status']??'open')!=='open'||!empty($roundState['maintenance_fee_applied'])||!empty($roundState['dividends_applied'])){
                throw new DomainException('Esta rodada já foi encerrada. A taxa e os dividendos não serão aplicados novamente.');
            }
            if(!$this->rounds->closeRound($sid,$round)){
                throw new DomainException('Esta rodada já foi encerrada.');
            }

            $teams=array_values(array_filter($this->teams->getBySessionId($sid),fn($t)=>($t['status']??'active')==='active' && (int)($t['is_eliminated']??0)===0));
            $roundFee=max(0.0,(float)$s['round_fee']);
            foreach($teams as &$t){
                $availableCash=max(0.0,(float)$t['cash_balance']);
                $paid=min($availableCash,$roundFee);
                $newCash=max(0.0,$availableCash-$paid);
                $this->teams->updateBalances((int)$t['id'],$newCash,(float)$t['btc_balance'],(int)$t['nft_balance'],(int)$t['pool_shares']);
                $t['cash_balance']=$newCash;

                if($paid+0.00001>=$roundFee){
                    $this->events->create($sid,$round,(int)$t['id'],'maintenance_fee','Equipe '.$t['name'].' pagou '.$this->formatMoney($paid).' de taxa de manutenção.',-$paid,0,0,0);
                }else{
                    $this->events->create($sid,$round,(int)$t['id'],'maintenance_fee_partial','Equipe '.$t['name'].' não tinha caixa suficiente para pagar a taxa completa.',-$paid,0,0,0);
                }
            }
            unset($t);

            $totalShares=0;
            foreach($teams as $t){$totalShares+=(int)$t['pool_shares'];}
            $poolNfts=$totalShares;
            $totalValue=$totalShares*(float)$s['nft_pool_value'];
            $yieldTotal=$totalValue*(float)$s['pool_yield_rate'];
            $yieldPer=$totalShares>0?$yieldTotal/$totalShares:0.0;

            if($totalShares>0){
                foreach($teams as $t){
                    $shares=(int)$t['pool_shares'];
                    if($shares<=0)continue;
                    $teamYield=$shares*$yieldPer;
                    $this->teams->updateBalances((int)$t['id'],(float)$t['cash_balance']+$teamYield,(float)$t['btc_balance'],(int)$t['nft_balance'],$shares);
                    $this->events->create($sid,$round,(int)$t['id'],'pool_dividend','Equipe '.$t['name'].' recebeu '.$this->formatMoney($teamYield).' em dividendos da piscina.',$teamYield,0,0,0);
                }
            }else{
                $this->events->create($sid,$round,null,'pool_dividend_empty','A piscina não distribuiu dividendos nesta rodada porque ainda não havia NFTs depositados.',0,0,0,0);
            }

            $this->events->create($sid,$round,null,'round_economy_summary','Rodada '.$round.' encerrada. A piscina tinha '.$totalShares.' '.($totalShares===1?'cota':'cotas').' e distribuiu '.$this->formatMoney($yieldTotal).' em dividendos.',0,0,0,0);
            $this->pool->updateState($sid,$poolNfts,$totalShares,$totalValue,$yieldPer,$this->determinePoolStatus($poolNfts,$totalShares));
            $this->rounds->markEconomyApplied($sid,$round);

            $nextRound=$round;
            $totalRounds=(int)($s['total_rounds']??0);
            if($totalRounds<=0||$round<$totalRounds){
                $nextRound=$round+1;
                $this->sessions->incrementRound($sid);
                $this->rounds->createRound($sid,$nextRound);
                $this->events->create($sid,$nextRound,null,'round_started','Rodada '.$nextRound.' iniciada.',0,0,0,0);
            }else{
                $this->pdo->prepare("UPDATE liquidity_sessions SET session_phase='semifinal', updated_at=NOW() WHERE id=?")->execute([$sid]);
                $this->events->create($sid,$round,null,'semifinal_preparation','Rodada regular final encerrada. O jogo entrou em preparação para a semifinal.',0,0,0,0);
            }

            $this->pdo->commit();
            return ['message'=>'Rodada encerrada com economia aplicada.','closed_round'=>$round,'next_round'=>$nextRound,'total_shares'=>$totalShares,'total_dividend'=>$yieldTotal,'dividend_per_share'=>$yieldPer];
        }catch(\Throwable $e){
            if($this->pdo->inTransaction())$this->pdo->rollBack();
            throw $e;
        }
    }
    public function evaluateSemifinal($sid):array{
        $sid=(int)$sid;
        $s=$this->mustSession($sid);
        if($this->isFinalClosed($s))throw new DomainException('A final já foi encerrada.');

        $round=(int)$s['current_round'];
        $wasEvaluated=in_array((string)($s['session_phase']??'regular'),['semifinal_evaluated','final'],true);

        $this->pdo->beginTransaction();
        try{
            $teams=$this->teams->getBySessionId($sid);
            $classified=0;
            $eliminated=0;

            $this->events->create($sid,$round,null,$wasEvaluated?'semifinal_reevaluated':'semifinal_evaluated',$wasEvaluated?'Semifinal reavaliada.':'Semifinal avaliada.',0,0,0,0);

            foreach($teams as $t){
                $nftsInHand=(int)$t['nft_balance'];
                $ok=$nftsInHand>=1;
                if($ok){$classified++;}else{$eliminated++;}

                $this->pdo->prepare('UPDATE liquidity_teams SET is_eliminated=?, qualified_for_final=?, final_status=NULL, final_score=NULL, updated_at=NOW() WHERE id=?')->execute([$ok?0:1,$ok?1:0,(int)$t['id']]);
                $this->events->create(
                    $sid,
                    $round,
                    (int)$t['id'],
                    $ok?'semifinal_classified':'semifinal_eliminated',
                    $ok
                        ? 'Time '.$t['name'].' classificado para a final com '.$nftsInHand.' '.($nftsInHand===1?'NFT':'NFTs').' em mãos.'
                        : 'Time '.$t['name'].' eliminado na semifinal por não possuir NFT em mãos.',
                    0,
                    0,
                    0,
                    0
                );
            }

            $this->pdo->prepare("UPDATE liquidity_sessions SET session_phase='semifinal_evaluated', updated_at=NOW() WHERE id=?")->execute([$sid]);
            $this->pdo->commit();
            return ['classified'=>$classified,'eliminated'=>$eliminated,'reevaluated'=>$wasEvaluated];
        }catch(\Throwable $e){
            if($this->pdo->inTransaction())$this->pdo->rollBack();
            throw $e;
        }
    }
    private function finalStatusForTeam(array $team):string{
        if(!empty($team['final_status'])) return (string)$team['final_status'];
        if(!empty($team['is_eliminated'])) return 'Eliminado na semifinal';
        if(!empty($team['qualified_for_final'])) return 'Classificado para a final';
        return 'Em jogo';
    }
    public function closeFinal($sid):array{
        $sid=(int)$sid;
        $s=$this->mustSession($sid);
        if($this->isFinalClosed($s))throw new DomainException('A final já foi encerrada.');
        if(!in_array((string)($s['session_phase']??'regular'),['semifinal_evaluated','final'],true))throw new DomainException('A semifinal precisa ser avaliada antes da final.');

        $round=(int)$s['current_round'];
        $teams=$this->teams->getBySessionId($sid);
        $finalists=array_values(array_filter($teams,fn($t)=>!empty($t['qualified_for_final']) && empty($t['is_eliminated'])));
        if(!$finalists)throw new DomainException('Não há times classificados para encerrar a final.');
        usort($finalists,fn($a,$b)=>((float)$b['cash_balance']<=>(float)$a['cash_balance']) ?: ((string)$a['name']<=>(string)$b['name']));
        $maxCash=(float)$finalists[0]['cash_balance'];
        $winners=array_values(array_filter($finalists,fn($t)=>abs((float)$t['cash_balance']-$maxCash)<0.00001));
        $isTie=count($winners)>1;
        $winnerIds=array_map(fn($t)=>(int)$t['id'],$winners);

        $this->pdo->beginTransaction();
        try{
            $this->events->create($sid,$round,null,$isTie?'final_closed_tie':'final_closed',$isTie?'Final encerrada com empate. Critério: maior caixa em reais.':'Final encerrada. Critério: maior caixa em reais.',0,0,0,0);
            foreach($finalists as $t){
                $cash=(float)$t['cash_balance'];
                $status=in_array((int)$t['id'],$winnerIds,true)?($isTie?'Vencedor empatado':'Vencedor'):'Finalista';
                $this->teams->updateFinalResult((int)$t['id'],$cash,$status);
            }
            foreach($teams as $t){
                if(!empty($t['is_eliminated'])){
                    $this->teams->updateFinalResult((int)$t['id'],(float)$t['cash_balance'],'Eliminado na semifinal');
                }
            }
            if($isTie){
                $names=array_map(fn($t)=>(string)$t['name'],$winners);
                $this->events->create($sid,$round,null,'final_winners_tie','Times '.$this->formatNames($names).' venceram empatados com '.$this->formatMoney($maxCash).' em caixa. Critério: caixa em reais.',0,0,0,0);
            }else{
                $winner=$winners[0];
                $this->events->create($sid,$round,(int)$winner['id'],'final_winner','Time '.$winner['name'].' venceu a final com '.$this->formatMoney($maxCash).' em caixa. Critério: caixa em reais.',0,0,0,0);
            }
            foreach($finalists as $t){
                if(in_array((int)$t['id'],$winnerIds,true))continue;
                $this->events->create($sid,$round,(int)$t['id'],'finalist','Time '.$t['name'].' terminou como finalista com '.$this->formatMoney((float)$t['cash_balance']).' em caixa. Critério: caixa em reais.',0,0,0,0);
            }
            $this->rounds->closeRound($sid,$round);
            $this->pdo->prepare("UPDATE liquidity_sessions SET session_phase='final_closed', status='closed', updated_at=NOW() WHERE id=?")->execute([$sid]);
            $this->pdo->commit();
            return ['winners'=>$winners,'finalists'=>$finalists,'tie'=>$isTie,'max_cash'=>$maxCash];
        }catch(\Throwable $e){
            if($this->pdo->inTransaction())$this->pdo->rollBack();
            throw $e;
        }
    }
    private function formatNames(array $names):string{if(count($names)<=1)return $names[0]??'';$last=array_pop($names);return implode(', ',$names).' e '.$last;}
    public function calculatePartialScore($t,$s):float{return (float)$t['cash_balance']+((float)$t['btc_balance']*(float)$s['btc_sell_price'])+((int)$t['nft_balance']*(float)$s['nft_sell_price'])+((int)$t['pool_shares']*(float)$s['share_sell_price']);}
    public function calculateFinalCashScore($t):float{return (float)$t['cash_balance'];}
    public function calcularRankingGeral($sid):array{
        $s=$this->mustSession((int)$sid);
        $teams=$this->teams->getBySessionId((int)$sid);
        foreach($teams as &$t){
            $t['score']=$this->calculatePartialScore($t,$s);
            $t['estimated_wealth']=$t['score'];
            $t['final_cash_score']=$this->calculateFinalCashScore($t);
            $t['display_status']=$this->finalStatusForTeam($t);
        }
        unset($t);
        usort($teams,fn($a,$b)=>
            ((float)$b['estimated_wealth']<=>(float)$a['estimated_wealth'])
            ?: ((float)$b['cash_balance']<=>(float)$a['cash_balance'])
            ?: ((string)$a['name']<=>(string)$b['name'])
        );
        foreach($teams as $i=>&$t){$t['general_position']=$i+1;}
        unset($t);
        return $teams;
    }
    public function calcularRankingFinal($sid):array{
        $s=$this->mustSession((int)$sid);
        $finalClosed=$this->isFinalClosed($s);
        $teams=$this->teams->getBySessionId((int)$sid);
        $finalists=array_values(array_filter($teams,fn($t)=>!empty($t['qualified_for_final']) && empty($t['is_eliminated'])));
        usort($finalists,fn($a,$b)=>((float)$b['cash_balance']<=>(float)$a['cash_balance']) ?: ((string)$a['name']<=>(string)$b['name']));
        $topCash=$finalists? (float)$finalists[0]['cash_balance'] : null;
        $topTieCount=$topCash===null?0:count(array_filter($finalists,fn($t)=>abs((float)$t['cash_balance']-$topCash)<0.00001));
        $previous=null;$position=0;$seen=0;
        foreach($finalists as &$t){
            $seen++;$cash=(float)$t['cash_balance'];
            if($previous===null||abs($cash-$previous)>0.00001){$position=$seen;$previous=$cash;}
            $t['final_position']=$position;
            $t['final_cash_score']=$cash;
            if($finalClosed){
                $t['display_status']=$position===1?($topTieCount>1?'Vencedor empatado':'Vencedor'):'Finalista';
            }else{
                $t['display_status']='Classificado para a final';
            }
        }
        unset($t);
        return $finalists;
    }
    public function getRanking($sid):array{return $this->calcularRankingGeral((int)$sid);}
    public function getFinalRanking($sid):array{return $this->calcularRankingFinal((int)$sid);}
    public function updatePoolTotals($sid):void{$s=$this->mustSession((int)$sid);$p=$this->pool->findBySessionId((int)$sid);$total=(int)$p['pool_nfts']*(float)$s['nft_pool_value'];$yield=((int)$p['total_shares']>0)?($total*(float)$s['pool_yield_rate'])/(int)$p['total_shares']:0;$this->pool->updateState((int)$sid,(int)$p['pool_nfts'],(int)$p['total_shares'],$total,$yield,$this->determinePoolStatus((int)$p['pool_nfts'],(int)$p['total_shares']));}
    public function updatePoolStatus($sid):void{$this->updatePoolTotals($sid);} public function determinePoolStatus($n,$ts):string{if($n<=0)return 'empty';if($n===1)return 'tense';if($n>=2 && $ts>=1)return 'stable';if($n>=1 && $ts===0)return 'critical';return 'collapsed';}
    public function closeSession($sid):void{$this->closeFinal($sid);} public function getProjectorState($sid):array{return ['session'=>$this->mustSession((int)$sid),'pool'=>$this->pool->findBySessionId((int)$sid),'ranking'=>$this->getRanking((int)$sid),'final_ranking'=>$this->getFinalRanking((int)$sid),'feed'=>$this->events->getRecentBySession((int)$sid,20)];}
}
