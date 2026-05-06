<?php declare(strict_types=1); namespace App\Repositories; use PDO;
final class LiquidityPredictionResolutionRepository{private $pdo;

    public function __construct($pdo = null){$this->pdo=$pdo??\App\Core\Database::connection();}
public function create($marketId,$winningOptionId,$resolvedBy,$notes):void{$this->pdo->prepare('INSERT INTO liquidity_prediction_resolutions (market_id,winning_option_id,resolved_by,resolution_notes,resolved_at) VALUES (?,?,?,?,NOW())')->execute([$marketId,$winningOptionId,$resolvedBy,$notes]);}
public function existsForMarket($id):bool{$s=$this->pdo->prepare('SELECT COUNT(*) FROM liquidity_prediction_resolutions WHERE market_id=?');$s->execute([$id]);return (int)$s->fetchColumn()>0;}
public function findByMarketId($id){$s=$this->pdo->prepare('SELECT * FROM liquidity_prediction_resolutions WHERE market_id=?');$s->execute([$id]);return $s->fetch()?:null;}}
