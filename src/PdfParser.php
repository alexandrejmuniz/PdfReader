<?php

include ( 'src/PdfToText.phpclass' ) ;

class PdfParser
{
    protected $input_dir_files;

    protected $output_dir_files;

    public $filename;

    public $pdf;

    public $structure = array();

    public $db_instance;

    public function __construct($host,$user,$pass,$database)
    {
        $this->db_instance = new mysqli($host, $user,$pass,$database);    
        
        $this->db_instance->query("SET NAMES 'utf8'");
        $this->db_instance->query('SET character_set_connection=utf8');
        $this->db_instance->query('SET character_set_client=utf8');
        $this->db_instance->query('SET character_set_results=utf8');
    }

    public function setInput_dir_files($input_dir_files)
    {
        $this->input_dir_files = $input_dir_files;
        return $this;
    }

    public function getInput_dir_files()
    {
        return $this->input_dir_files;
    }

    public function setOutput_dir_files($output_dir_files)
    {
        $this->output_dir_files = $output_dir_files;
        return $this;
    }

    public function getOutput_dir_files()
    {
        return $this->output_dir_files;
    }

    public function setFilename($filename)
    {
        $this->filename = $filename;
        return $this;
    }

    public function getFilename()
    {
        return $this->filename;
    }

    public function getStructure()
    {
        return $this->structure;
    }

    public function setPdf()
    {
        $this->pdf = new PdfToText ( 
                    $this->getInput_dir_files().
                    $this->getFilename() 
                ) ;        
        return $this;
    }

    public function getPdf()
    {
        return $this->pdf;
    }

    public function loadInput_dir_files()
    {
        if ($handle = opendir($this->getInput_dir_files())) {
            while (false !== ($file = readdir($handle))) {
                if ($file != "." && $file != "..") {
                    $path_parts = pathinfo($file);
                    if($path_parts['extension']=='pdf')
                    {
                        $this->structure[] = array(
                                        'filepath'=>$file,
                                        'name'=>$path_parts['filename'],
                                        'extension'=>$path_parts['extension'],
                                        
                                    );                            
                    }
                }
            }
            closedir($handle);
        }   
        return $this;
    }
    
    public function load_all_content($type=null)
    {
        $load_query = "SELECT * FROM arquivo ".( $type!=null?' WHERE status = '.$type:NULL );
        $res = $this->db_instance->query($load_query);
        
        $itens = $res->fetch_all(MYSQLI_ASSOC);
        
        return $itens;
        
    }

    public function persist()
    {
        $totals = array();
        $totals['leituras_validas'] = 0;
        $totals['leituras_invalidas'] = 0;
        $totals['leituras_total'] = 0;
        
        foreach($this->getStructure() as $item)
        {
            $this->setFilename($item['filepath']);
            $this->setPdf();
            
            $totals['leituras_total'] = $totals['leituras_total']+1;
            
            $content = $this->getPdf()->Text;

            # Apenas arquivos contendo mais de 1 caracter
            if(strlen(trim($content))>1)
            {
                
                $totals['leituras_validas'] = $totals['leituras_validas']++;

                $found_query = "SELECT * FROM arquivo WHERE nome = '".$item['filepath']."' LIMIT 1";

                if($result = $this->db_instance->query($found_query))
                {
                    if($result->num_rows>0)
                    {
                        $itens = $result->fetch_all(MYSQLI_ASSOC);

                        $item_found = $itens[0];

                        $update_query = "UPDATE arquivo SET 
                                                nome = '".$item['filepath']."', 
                                                conteudo = '".str_replace('  ','',$content)."' ,
                                                status = 1,
                                                descricao_analise = '' 
                                        WHERE id = ".$item_found['id'];

                        $this->db_instance->query($update_query);
                    }else{
                        $insert_query = "INSERT INTO arquivo(nome,conteudo,status,descricao_analise)
                                        VALUES('".$item['filepath']."','".str_replace('  ','',$content)."',1,'')";
                        $this->db_instance->query($insert_query);

                    }                   
                }

                $this->move_from_input_to_output($item['filepath']);
            }else{

                $found_query = "SELECT * FROM arquivo WHERE nome = '".$item['filepath']."' LIMIT 1";
                
                $fail_message = 'Arquivo contem conteúdo inferior a 2 caracteres';
                
                if($result = $this->db_instance->query($found_query))
                {
                    if($result->num_rows>0)
                    {
                        $itens = $result->fetch_all(MYSQLI_ASSOC);
                        
                        $item_found = $itens[0];
                        
                        $update_query = "UPDATE arquivo SET
                                                nome = '".$item['filepath']."',
                                                conteudo = '".str_replace('  ','',$content)."' ,
                                                status = 2,
                                                descricao_analise = '".$fail_message."'
                                        WHERE id = ".$item_found['id'];
                        
                        $this->db_instance->query($update_query);
                    }else{
                        $insert_query = "INSERT INTO arquivo(nome,conteudo,status,descricao_analise)
                                        VALUES('".$item['filepath']."','".str_replace('  ','',$content)."',2,'".$fail_message."')";
                        $this->db_instance->query($insert_query);
                        
                    }
                }
                
                $totals['leituras_invalidas'] = $totals['leituras_invalidas']+1;
            }
        }
        return $totals;
    }

    private function move_from_input_to_output($filename)
    {
        $in = $this->getInput_dir_files().$filename;
        $out = $this->getOutput_dir_files().$filename;

        if(copy($in,$out))
        {
            unlink($in);
        }        
    }

    public function raw_text()
    {
        foreach($this->getStructure() as $item)
        {
        $this->setFilename($item['filepath']);
        $this->setPdf();
        echo $this->getPdf()->Text .'<br /><hr />';
        echo strlen(trim($this->getPdf()->Text)) .'<br /><hr />';
        }
    }

}

