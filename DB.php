<?php
class BD
{
    private static $pdo = null;
    private static $table = null;

    private $columns = '*';       // colonnes sélectionnées
    private $wheres = [];
    private $joins = [];
    private $groupBy = '';
    private $having = '';
    private $order = '';
    private $limit = '';
    private $offset = '';

    // --- Connexion ---
    public static function connect($host, $db, $user, $pass, $charset = 'utf8mb4')
    {
        if (self::$pdo === null) {
            $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            try {
                self::$pdo = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                die("Erreur de connexion : " . $e->getMessage());
            }
        }
        return self::$pdo;
    }

    // --- Définir table ---
    public static function table($table)
    {
        $instance = new self;
        $instance::$table = $table;
        return $instance;
    }

    // --- Colonnes à sélectionner ---
    public function select(...$cols)
    {
        $this->columns = $cols ? implode(', ', $cols) : '*';
        return $this;
    }

    // --- JOINS ---
    public function join($table, $first, $operator, $second, $type='INNER')
    {
        $this->joins[] = strtoupper($type) . " JOIN $table ON $first $operator $second";
        return $this;
    }
    public function innerJoin($table, $first, $operator, $second) { return $this->join($table, $first, $operator, $second, 'INNER'); }
    public function leftJoin($table, $first, $operator, $second) { return $this->join($table, $first, $operator, $second, 'LEFT'); }
    public function rightJoin($table, $first, $operator, $second) { return $this->join($table, $first, $operator, $second, 'RIGHT'); }
    public function crossJoin($table) { $this->joins[] = "CROSS JOIN $table"; return $this; }
    public function selfJoin($table, $first, $operator, $second) { return $this->join($table, $first, $operator, $second, 'INNER'); }

    private function buildJoin() { return empty($this->joins) ? '' : ' ' . implode(' ', $this->joins); }

    // --- WHERE ---
    public function where($column, $operator=null, $value=null) { if ($value===null){$value=$operator;$operator='=';} $this->wheres[]=['AND',$column,$operator,$value]; return $this; }
    public function orWhere($column, $operator=null, $value=null) { if ($value===null){$value=$operator;$operator='=';} $this->wheres[]=['OR',$column,$operator,$value]; return $this; }
    public function whereIn($column, array $values) { $this->wheres[]=['IN',$column,$values]; return $this; }
    public function whereNotIn($column, array $values) { $this->wheres[]=['NOTIN',$column,$values]; return $this; }
    public function whereNull($column) { $this->wheres[]=['NULL',$column]; return $this; }
    public function whereNotNull($column) { $this->wheres[]=['NOTNULL',$column]; return $this; }
    public function whereBetween($column, $start, $end) { $this->wheres[]=['BETWEEN',$column,$start,$end]; return $this; }
    public function whereLike($column, $pattern) { $this->wheres[]=['LIKE',$column,$pattern]; return $this; }
    public function whereCase($column, $cases, $alias) { $this->wheres[]=['CASE',$column,$cases,$alias]; return $this; }

    private function buildWhere(&$params)
    {
        if(empty($this->wheres)) return '';
        $sql=' WHERE ';
        $parts=[];
        foreach($this->wheres as $i=>$w){
            $type=$w[0];
            switch($type){
                case 'AND':
                case 'OR':
                    [$_,$col,$op,$val]=$w; $p=":w$i"; $parts[]=($i>0?" $type ":"")."$col $op $p"; $params[$p]=$val; break;
                case 'IN':
                    [$_,$col,$vals]=$w; $ph=[]; foreach($vals as $k=>$v){$pi=":w{$i}_$k";$ph[]=$pi;$params[$pi]=$v;} $parts[]=($i>0?" AND ":"")."$col IN (".implode(',',$ph).")"; break;
                case 'NOTIN':
                    [$_,$col,$vals]=$w; $ph=[]; foreach($vals as $k=>$v){$pi=":w{$i}_$k";$ph[]=$pi;$params[$pi]=$v;} $parts[]=($i>0?" AND ":"")."$col NOT IN (".implode(',',$ph).")"; break;
                case 'NULL': [$_,$col]=$w;$parts[]=($i>0?" AND ":"")."$col IS NULL"; break;
                case 'NOTNULL': [$_,$col]=$w;$parts[]=($i>0?" AND ":"")."$col IS NOT NULL"; break;
                case 'BETWEEN': [$_,$col,$start,$end]=$w; $p1=":w{$i}_s"; $p2=":w{$i}_e"; $parts[]=($i>0?" AND ":"")."$col BETWEEN $p1 AND $p2"; $params[$p1]=$start; $params[$p2]=$end; break;
                case 'LIKE': [$_,$col,$pat]=$w; $p=":w$i"; $parts[]=($i>0?" AND ":"")."$col LIKE $p"; $params[$p]=$pat; break;
            }
        }
        return $sql.implode('',$parts);
    }

    // --- GROUP BY / HAVING ---
    public function groupBy($columns){ $this->groupBy=" GROUP BY $columns"; return $this; }
    public function having($condition){ $this->having=" HAVING $condition"; return $this; }

    // --- ORDER / LIMIT / OFFSET ---
    public function orderBy($col,$dir='ASC'){ $this->order=" ORDER BY $col ".strtoupper($dir); return $this; }
    public function limit($num){ $this->limit=" LIMIT $num"; return $this; }
    public function offset($num){ $this->offset=" OFFSET $num"; return $this; }

    // --- SELECT ---
    public function get()
    {
        $params=[];
        $sql="SELECT {$this->columns} FROM ".self::$table
             .$this->buildJoin()
             .$this->buildWhere($params)
             .$this->groupBy
             .$this->having
             .$this->order
             .$this->limit
             .$this->offset;
        $stmt=self::$pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function first(){ $this->limit(1); $r=$this->get(); return $r[0]??null; }

    public function count(){ $params=[]; $sql="SELECT COUNT(*) as total FROM ".self::$table.$this->buildJoin().$this->buildWhere($params); $stmt=self::$pdo->prepare($sql); $stmt->execute($params); $row=$stmt->fetch(); return $row['total']??0; }

    public function exists(){ return $this->count()>0; }

    // --- INSERT / UPDATE / DELETE ---
    public static function insert($table,$data){ $cols=implode(',',array_keys($data)); $ph=':'.implode(',:',array_keys($data)); $sql="INSERT INTO $table ($cols) VALUES ($ph)"; $stmt=self::$pdo->prepare($sql); return $stmt->execute($data); }

    public function update($data){ $set=[]; $params=[]; foreach($data as $k=>$v){ $set[]="$k=:$k"; $params[":$k"]=$v; } $sql="UPDATE ".self::$table." SET ".implode(',',$set).$this->buildJoin().$this->buildWhere($params); $stmt=self::$pdo->prepare($sql); return $stmt->execute($params); }

    public function delete(){ $params=[]; $sql="DELETE FROM ".self::$table.$this->buildJoin().$this->buildWhere($params); $stmt=self::$pdo->prepare($sql); return $stmt->execute($params); }

    // --- Requêtes brutes ---
    public static function selectRaw($sql,$params=[]){ $stmt=self::$pdo->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(); }
    public static function statement($sql,$params=[]){ $stmt=self::$pdo->prepare($sql); return $stmt->execute($params); }

    // --- Fonctions d'agrégation ---
    public function min($col){ $params=[]; $sql="SELECT MIN($col) as val FROM ".self::$table.$this->buildJoin().$this->buildWhere($params); $stmt=self::$pdo->prepare($sql); $stmt->execute($params); $row=$stmt->fetch(); return $row['val']??null; }
    public function max($col){ $params=[]; $sql="SELECT MAX($col) as val FROM ".self::$table.$this->buildJoin().$this->buildWhere($params); $stmt=self::$pdo->prepare($sql); $stmt->execute($params); $row=$stmt->fetch(); return $row['val']??null; }
    public function sum($col){ $params=[]; $sql="SELECT SUM($col) as val FROM ".self::$table.$this->buildJoin().$this->buildWhere($params); $stmt=self::$pdo->prepare($sql); $stmt->execute($params); $row=$stmt->fetch(); return $row['val']??0; }
    public function avg($col){ $params=[]; $sql="SELECT AVG($col) as val FROM ".self::$table.$this->buildJoin().$this->buildWhere($params); $stmt=self::$pdo->prepare($sql); $stmt->execute($params); $row=$stmt->fetch(); return $row['val']??0; }
}