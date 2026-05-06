<?php declare(strict_types=1);
namespace App\Repositories; use PDO;
final class LiquidityPredictionMarketRepository{private $pdo;

    public function __construct($pdo = null){$this->pdo=$pdo??\App\Core\Database::connection();}
public function create($sessionId,$question,$description,$marketType,$closesRound,$createdBy):int{$s=$this->pdo->prepare('INSERT INTO liquidity_prediction_markets (session_id,question,description,market_type,closes_round,created_by) VALUES (?,?,?,?,?,?)');$s->execute([$sessionId,$question,$description,$marketType,$closesRound,$createdBy]);return (int)$this->pdo->lastInsertId();}
public function findById($id){$s=$this->pdo->prepare('SELECT * FROM liquidity_prediction_markets WHERE id=?');$s->execute([$id]);return $s->fetch()?:null;}
public function getBySessionId($sid){$s=$this->pdo->prepare('SELECT * FROM liquidity_prediction_markets WHERE session_id=? ORDER BY id DESC');$s->execute([$sid]);return $s->fetchAll();}
public function getOpenBySessionId($sid){$s=$this->pdo->prepare("SELECT * FROM liquidity_prediction_markets WHERE session_id=? AND status='open' ORDER BY id DESC");$s->execute([$sid]);return $s->fetchAll();}
public function setStatus($id,$st):void{$this->pdo->prepare('UPDATE liquidity_prediction_markets SET status=?, updated_at=NOW() WHERE id=?')->execute([$st,$id]);}
public function setResolvedOption($id,$opt):void{$this->pdo->prepare('UPDATE liquidity_prediction_markets SET resolved_option_id=?, updated_at=NOW() WHERE id=?')->execute([$opt,$id]);}
public function optionBelongsToMarket($opt,$market):bool{$s=$this->pdo->prepare('SELECT COUNT(*) FROM liquidity_prediction_options WHERE id=? AND market_id=?');$s->execute([$opt,$market]);return (int)$s->fetchColumn()>0;}}
