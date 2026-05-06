<?php declare(strict_types=1); namespace App\Repositories; use PDO;
final class LiquidityPredictionOptionRepository{public function __construct(private ?PDO $pdo=null){$this->pdo=$this->pdo??\App\Core\Database::connection();}
public function createMany($marketId,array $options):void{$s=$this->pdo->prepare('INSERT INTO liquidity_prediction_options (market_id,label,sort_order) VALUES (?,?,?)');foreach($options as $i=>$o){$s->execute([$marketId,$o,$i]);}}
public function getByMarketId($id){$s=$this->pdo->prepare('SELECT * FROM liquidity_prediction_options WHERE market_id=? ORDER BY sort_order,id');$s->execute([$id]);return $s->fetchAll();}
public function findById($id){$s=$this->pdo->prepare('SELECT * FROM liquidity_prediction_options WHERE id=?');$s->execute([$id]);return $s->fetch()?:null;}
public function incrementWeight($id,$a):void{$this->pdo->prepare('UPDATE liquidity_prediction_options SET weight_value = weight_value + ?, updated_at=NOW() WHERE id=?')->execute([$a,$id]);}
public function updateProbability($id,$p):void{$this->pdo->prepare('UPDATE liquidity_prediction_options SET probability_value=?, updated_at=NOW() WHERE id=?')->execute([$p,$id]);}}
