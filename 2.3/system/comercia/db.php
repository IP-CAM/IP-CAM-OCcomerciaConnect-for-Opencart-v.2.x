<?php
    namespace comercia;
    class db{


        public function saveDataObject($table, $data,$keys=null)
        {
            if(!$keys){
                $keys[]=$table."_id";
            }
            $exists=$this->recordExists($table,$data,$keys);

            if($exists){
                $query = "update " . DB_PREFIX . $table . " set ";
            }else {
                $query = "insert into " . DB_PREFIX . $table . " set ";
            }
            $i = 0;
            foreach ($data as $key => $value) {
                if ($i) {
                    $query .= ",";
                }
                $query .= "`" . $key . "`='" . $this->_db()->escape($value) . "'";
                $i++;
            }
            if($exists){
                $query.=" where ";
                $query.=$this->whereForKeys($table,$data,$keys);
            }
            $this->_db()->query($query);
            if(!$exists) {
                return $this->_db()->getLastId();
            }

            if(count($keys)==1) {
                return $data[$keys[0]];
            }
            $result=array();
            foreach($keys as $key){
                $result[]=$data[$key];
            }
            return $result;
        }

        public function recordExists($table,$data,$keys=null){


            $query="select * from `".DB_PREFIX.$table."` where ";
            $query.=$this->whereForKeys($table,$data,$keys);
            $query.=" limit 0,1";
            $query=$this->_db()->query($query);
            return $query->num_rows?true:false;
        }

        private function whereForKeys($table,$data,$keys=null){
            if(!$keys){
                $keys[]=$table."_id";
            }
            $result="";
            foreach($keys as $akey=>$key){
                if($akey>0){
                    $result.=" && ";
                }
                $result.=" `".$key."`='".$data[$key]."' ";
            }
            return $result;
        }

        public function saveDataObjectArray($table,$data){
            foreach($data as $obj){
                $this->saveDataObject($table,$obj);
            }
        }
        
        private function _db(){
            global $registry;
            return $registry->get("db");
        }
    }
?>