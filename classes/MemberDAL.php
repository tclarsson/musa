<?php
class MemberDAL {

    private $db = null;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function findAll()
    {
        $statement = "SELECT * FROM tbMembers;";

        try {
            $statement = $this->db->query($statement);
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
            return $result;
        } catch (\PDOException $e) {
            exit($e->getMessage());
        }
    }

    public function find($id)
    {
        $statement = "SELECT * FROM tbMembers WHERE user_id = ?;";
        //print_r($statement);

        try {
            $statement = $this->db->prepare($statement);
            $statement->execute(array($id));
            $result = $statement->fetch(\PDO::FETCH_ASSOC);
            return $result;
        } catch (\PDOException $e) {
            exit($e->getMessage());
        }    
    }

    public function create(Array $input)
    {
        $sql  = "INSERT INTO tbMembers";
        $sql .= " (`".implode("`, `", array_keys($input))."`)";
        $sql .= " VALUES ('".implode("', '", $input)."'); ";
        //print_r($sql);

        try {
            $r=$this->db->query($sql)->rowCount();
            //print_r($r);
            return $r;
        } catch (\PDOException $e) {
            exit($e->getMessage());
        }    
    }

    public function update($id, Array $input)
    {
        $set = implode('=?, ', array_keys($input)) . '=?';

        $sql = "UPDATE tbMembers SET $set WHERE user_id = ?";

        $sql_params = array_values($input);
        $sql_params[] = $id;

        //print_r($sql);

        try {
            $st = $this->db->prepare($sql);
            $st->execute($sql_params);
            return $st->rowCount();
        } catch (\PDOException $e) {
            exit($e->getMessage());
        }    
    }

    public function delete($id)
    {
        $statement = "DELETE FROM tbMembers WHERE user_id = ?;";

        try {
            $statement = $this->db->prepare($statement);
            $statement->execute(array($id));
            return $statement->rowCount();
        } catch (\PDOException $e) {
            exit($e->getMessage());
        }    
    }
}
?>