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

    public function submitTeamAction($sid,$tid,$a,$q=1,$extra=[]):void{
        $sid=(int)$sid;
        $tid=(int)$tid;
        $q=$q===null?null:(float)$q;

        if(!in_array($a,self::ACTIONS,true))throw new DomainException('Ação inválida.');

        $s=$this->mustSession($sid);
        if(($s['status']??'')!=='active') throw new DomainException('Sessão não está ativa.');

        $t=$this->teams->findById($tid);
        $p=$this->pool->findBySessionId($sid);
        if(!$t||!$p||(int)$t['session_id']!==$sid) throw new DomainException('Equipe inválida.');
        if((int)($t['is_eliminated']??0)===1) throw new DomainException('Equipe eliminada não pode agir.');

        $phase=(string)($s['session_phase']??'regular');
        if(!in_array($phase,['regular','semifinal','final'],true)) throw new DomainException('Fase encerrada.');

        $r=(int)$s['current_round'];
        if(!$this->rounds->hasOpenRound($sid,$r)) throw new DomainException('Rodada encerrada. Aguarde o professor iniciar a próxima rodada.');
        if($this->actions->hasTeamActed($sid,$r,$tid)) throw new DomainException('Este time já usou sua ação nesta rodada.');

        $this->pdo->beginTransaction();
        try {
            $result=['cash'=>0.0,'btc'=>0.0,'nft'=>0,'share'=>0,'poolN'=>(int)$p['pool_nfts'],'tot'=>(int)$p['total_shares'],'msg'=>'Você passou a vez.'];
            if($a==='deposit_nft')$result=$this->depositNft($t,$s,$p);
            elseif($a==='withdraw_nft_btc')$result=$this->withdrawNftWithBtc($t,$s,$p);
            elseif($a==='withdraw_nft_cash')$result=$this->withdrawNftWithCash($t,$s,$p);
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
    public function depositNft($t,$s,$p):array{if((int)$t['nft_balance']<1)throw new DomainException('Este time não possui NFT em mãos para depositar.');return ['cash'=>0,'btc'=>(float)$s['btc_deposit_reward'],'nft'=>-1,'share'=>1,'poolN'=>(int)$p['pool_nfts']+1,'tot'=>(int)$p['total_shares']+1,'msg'=>'Time '.$t['name'].' depositou 1 NFT na piscina, recebeu '.rtrim(rtrim(number_format((float)$s['btc_deposit_reward'],2,'.',''),'0'),'.').' BTC e ganhou 1 cota.'];}
    public function withdrawNftWithBtc($t,$s,$p):array{if((int)$t['pool_shares']<1)throw new DomainException('Este time não possui cota para retirar NFT.');if((float)$t['btc_balance']<(float)$s['btc_withdraw_cost'])throw new DomainException('Saldo de BTC insuficiente.');if((int)$p['pool_nfts']<1)throw new DomainException('A piscina não possui NFT disponível para retirada.');return ['cash'=>0,'btc'=>-(float)$s['btc_withdraw_cost'],'nft'=>1,'share'=>-1,'poolN'=>(int)$p['pool_nfts']-1,'tot'=>(int)$p['total_shares']-1,'msg'=>'Time '.$t['name'].' retirou 1 NFT da piscina pagando '.rtrim(rtrim(number_format((float)$s['btc_withdraw_cost'],2,'.',''),'0'),'.').' BTC.'];}
    public function withdrawNftWithCash($t,$s,$p):array{if((int)$t['pool_shares']<1)throw new DomainException('Este time não possui cota para retirar NFT.');if((float)$t['cash_balance']<(float)$s['cash_withdraw_cost'])throw new DomainException('Caixa insuficiente.');if((int)$p['pool_nfts']<1)throw new DomainException('A piscina não possui NFT disponível para retirada.');return ['cash'=>-(float)$s['cash_withdraw_cost'],'btc'=>0,'nft'=>1,'share'=>-1,'poolN'=>(int)$p['pool_nfts']-1,'tot'=>(int)$p['total_shares']-1,'msg'=>'Time '.$t['name'].' retirou 1 NFT da piscina pagando R$ '.number_format((float)$s['cash_withdraw_cost'],2,',','.').'.'];}
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

    public function advanceRound($sid):array{
        $sid=(int)$sid;
        $s=$this->mustSession($sid);
        if(($s['status']??'')==='closed')throw new DomainException('Sessão encerrada.');

        $round=(int)$s['current_round'];
        $this->pdo->beginTransaction();
        try{
            $this->rounds->closeRound($sid,$round);
            $this->events->create($sid,$round,null,'round_closed','Rodada encerrada.',0,0,0,0);

            $teams=$this->teams->getBySessionId($sid);
            $roundFee=max(0.0,(float)$s['round_fee']);
            foreach($teams as &$t){
                $availableCash=max(0.0,(float)$t['cash_balance']);
                $paid=min($availableCash,$roundFee);
                $newCash=$availableCash-$paid;
                $this->teams->updateBalances((int)$t['id'],$newCash,(float)$t['btc_balance'],(int)$t['nft_balance'],(int)$t['pool_shares']);
                $t['cash_balance']=$newCash;

                if($paid+0.00001>=$roundFee){
                    $this->events->create($sid,$round,(int)$t['id'],'round_fee','Time '.$t['name'].' pagou '.$this->formatMoney($paid).' de taxa de participação.',-$paid,0,0,0);
                }else{
                    $this->events->create($sid,$round,(int)$t['id'],'round_fee_partial','Time '.$t['name'].' pagou taxa parcial de '.$this->formatMoney($paid).' por falta de saldo.',-$paid,0,0,0);
                }
            }
            unset($t);

            $pool=$this->pool->findBySessionId($sid);
            $poolNfts=(int)($pool['pool_nfts']??0);
            $totalShares=(int)($pool['total_shares']??0);
            $totalValue=$poolNfts*(float)$s['nft_pool_value'];
            $yieldTotal=$totalValue*(float)$s['pool_yield_rate'];
            $yieldPer=($poolNfts>0&&$totalShares>0)?$yieldTotal/$totalShares:0.0;

            $this->events->create($sid,$round,null,'pool_value','Piscina tinha '.$poolNfts.' NFTs depositadas, totalizando '.$this->formatMoney($totalValue).'.',0,0,0,0);
            $this->events->create($sid,$round,null,'pool_yield_total','Rendimento total da rodada: '.$this->formatMoney($yieldTotal).'.',0,0,0,0);
            $this->events->create($sid,$round,null,'pool_yield_per_share','Rendimento por cota: '.$this->formatMoney($yieldPer).'.',0,0,0,0);

            if($yieldTotal>0&&$yieldPer>0){
                foreach($teams as $t){
                    $shares=(int)$t['pool_shares'];
                    if($shares<=0)continue;
                    $teamYield=$shares*$yieldPer;
                    $this->teams->updateBalances((int)$t['id'],(float)$t['cash_balance']+$teamYield,(float)$t['btc_balance'],(int)$t['nft_balance'],$shares);
                    $this->events->create($sid,$round,(int)$t['id'],'pool_yield','Time '.$t['name'].' recebeu '.$this->formatMoney($teamYield).' de rendimento por '.$shares.' '.($shares===1?'cota':'cotas').'.',$teamYield,0,0,0);
                }
            }

            $this->pool->updateState($sid,$poolNfts,$totalShares,$totalValue,$yieldPer,$this->determinePoolStatus($poolNfts,$totalShares));

            $nextRound=$round+1;
            $this->sessions->incrementRound($sid);
            $this->rounds->createRound($sid,$nextRound);
            $this->events->create($sid,$nextRound,null,'round_started','Nova rodada iniciada. Todos os times podem agir novamente.',0,0,0,0);

            $this->pdo->commit();
            return ['message'=>'Rodada encerrada e nova rodada iniciada.'];
        }catch(\Throwable $e){
            if($this->pdo->inTransaction())$this->pdo->rollBack();
            throw $e;
        }
    }
    public function evaluateSemifinal($sid):void{$s=$this->mustSession((int)$sid);$teams=$this->teams->getBySessionId((int)$sid);foreach($teams as $t){$ok=(int)$t['nft_balance']>=1; $this->pdo->prepare('UPDATE liquidity_teams SET is_eliminated=?, qualified_for_final=?, updated_at=NOW() WHERE id=?')->execute([$ok?0:1,$ok?1:0,(int)$t['id']]); $this->events->create((int)$sid,(int)$s['current_round'],(int)$t['id'],'semifinal',$ok?'Equipe '.$t['name'].' passou para a final.':'Equipe '.$t['name'].' foi eliminada na semifinal por não ter NFT em mãos.',0,0,0,0);} $this->pdo->prepare("UPDATE liquidity_sessions SET session_phase='final', updated_at=NOW() WHERE id=?")->execute([(int)$sid]);}
    public function closeFinal($sid):void{$s=$this->mustSession((int)$sid);$teams=$this->teams->getBySessionId((int)$sid);usort($teams,fn($a,$b)=>(float)$b['cash_balance']<=>(float)$a['cash_balance']);$w=$teams[0]??null;foreach($teams as $t){$this->teams->updateFinalScore((int)$t['id'],(float)$t['cash_balance']);} if($w){$this->events->create((int)$sid,(int)$s['current_round'],(int)$w['id'],'final_winner','Equipe '.$w['name'].' venceu a final com R$ '.number_format((float)$w['cash_balance'],2,',','.'),0,0,0,0);} $this->pdo->prepare("UPDATE liquidity_sessions SET session_phase='closed', status='closed', updated_at=NOW() WHERE id=?")->execute([(int)$sid]);}
    public function calculatePartialScore($t,$s):float{return (float)$t['cash_balance']+((float)$t['btc_balance']*(float)$s['btc_sell_price'])+((int)$t['nft_balance']*(float)$s['nft_sell_price'])+((int)$t['pool_shares']*(float)$s['share_sell_price']);}
    public function calculateFinalCashScore($t):float{return (float)$t['cash_balance'];}
    public function getRanking($sid):array{$s=$this->mustSession((int)$sid);$teams=$this->teams->getBySessionId((int)$sid);foreach($teams as &$t){$t['score']=$this->calculatePartialScore($t,$s);$t['final_cash_score']=$this->calculateFinalCashScore($t);}usort($teams,fn($a,$b)=>$b['score']<=>$a['score']);return $teams;}
    public function updatePoolTotals($sid):void{$s=$this->mustSession((int)$sid);$p=$this->pool->findBySessionId((int)$sid);$total=(int)$p['pool_nfts']*(float)$s['nft_pool_value'];$yield=((int)$p['total_shares']>0)?($total*(float)$s['pool_yield_rate'])/(int)$p['total_shares']:0;$this->pool->updateState((int)$sid,(int)$p['pool_nfts'],(int)$p['total_shares'],$total,$yield,$this->determinePoolStatus((int)$p['pool_nfts'],(int)$p['total_shares']));}
    public function updatePoolStatus($sid):void{$this->updatePoolTotals($sid);} public function determinePoolStatus($n,$ts):string{if($n<=0)return 'empty';if($n===1)return 'tense';if($n>=2 && $ts>=1)return 'stable';if($n>=1 && $ts===0)return 'critical';return 'collapsed';}
    public function closeSession($sid):void{$this->closeFinal($sid);} public function getProjectorState($sid):array{return ['session'=>$this->mustSession((int)$sid),'pool'=>$this->pool->findBySessionId((int)$sid),'ranking'=>$this->getRanking((int)$sid),'feed'=>$this->events->getRecentBySession((int)$sid,20)];}
}
