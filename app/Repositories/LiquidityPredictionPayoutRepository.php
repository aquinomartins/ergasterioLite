<?php declare(strict_types=1); namespace App\Repositories; use PDO;
final class LiquidityPredictionPayoutRepository{private $pdo;

    public function __construct($pdo = null){$this->pdo=$pdo??\App\Core\Database::connection();}
public function create($marketId,$betId,$sessionId,$teamId,$optionId,$gross,$fee,$net):void{$this->pdo->prepare('INSERT INTO liquidity_prediction_payouts (market_id,bet_id,session_id,team_id,option_id,gross_amount,fee_amount,net_amount) VALUES (?,?,?,?,?,?,?,?)')->execute([$marketId,$betId,$sessionId,$teamId,$optionId,$gross,$fee,$net]);}
public function existsForBet($m,$b):bool{$s=$this->pdo->prepare('SELECT COUNT(*) FROM liquidity_prediction_payouts WHERE market_id=? AND bet_id=?');$s->execute([$m,$b]);return (int)$s->fetchColumn()>0;}
public function getByMarketId($id){$s=$this->pdo->prepare('SELECT * FROM liquidity_prediction_payouts WHERE market_id=? ORDER BY id DESC');$s->execute([$id]);return $s->fetchAll();}
public function getByTeamId($id){$s=$this->pdo->prepare('SELECT * FROM liquidity_prediction_payouts WHERE team_id=? ORDER BY id DESC');$s->execute([$id]);return $s->fetchAll();}}
