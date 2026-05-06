<?php declare(strict_types=1); namespace App\Controllers;
use App\Core\Controller; use App\Core\Csrf; use App\Core\Session; use App\Services\LiquidityPredictionMarketService; use DomainException;
final class LiquidityPredictionController extends Controller{private LiquidityPredictionMarketService $s; public function __construct(){$this->s=new LiquidityPredictionMarketService();}
private function json($d):void{header('Content-Type: application/json');echo json_encode($d);} 
public function createForm(string $sessionId):void{$this->view('liquidity.predictions.create',['sessionId'=>(int)$sessionId]);}
public function store(string $sessionId):void{if(!Csrf::verifyFromRequest())throw new DomainException('CSRF inválido.');$opts=$_POST['options']??[];if(($_POST['market_type']??'binary')==='binary' && count($opts)<2){$opts=['Sim','Não'];} $this->s->createMarket((int)$sessionId,(string)($_POST['question']??''),(string)($_POST['description']??''),$opts,(int)($_POST['closes_round']??0),Session::get('user_id'));$this->redirectTo('/liquidity/'.$sessionId);} 
public function show(string $marketId):void{$m=$this->s->getMarketDetail((int)$marketId);$this->view('liquidity.predictions.show',['market'=>$m]);}
public function close(string $marketId):void{if(!Csrf::verifyFromRequest())throw new DomainException('CSRF inválido.');$this->s->closeMarket((int)$marketId,Session::get('user_id'));$this->redirectTo('/liquidity/predictions/'.$marketId);} 
public function resolve(string $marketId):void{if(!Csrf::verifyFromRequest())throw new DomainException('CSRF inválido.');$this->s->resolveMarket((int)$marketId,(int)($_POST['winning_option_id']??0),Session::get('user_id'),(string)($_POST['resolution_notes']??''));$this->redirectTo('/liquidity/predictions/'.$marketId);} 
public function placeBet(string $marketId):void{if(!Csrf::verifyFromRequest())throw new DomainException('CSRF inválido.');$sid=(int)Session::get('liquidity_session_id',0);$tid=(int)Session::get('liquidity_team_id',0);$this->s->placeBet($sid,$tid,(int)$marketId,(int)($_POST['option_id']??0),(float)($_POST['amount']??0));$this->redirectTo('/liquidity/team/dashboard');}
public function listBySession(string $sessionId):void{$this->json($this->s->getMarketsForSession((int)$sessionId));}
public function getMarket(string $marketId):void{$this->json($this->s->getMarketDetail((int)$marketId));}}
