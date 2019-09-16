<?php

include ( 'src/PdfParser.php' ) ;

#dados do banco
$pdf = new PdfParser( 'localhost','root','','pdftotext' );

$pdf->setInput_dir_files( '_files/input/' )->
setOutput_dir_files( '_files/output/' )->
loadInput_dir_files( )->
persist(  );

#Todos os registros
#print_r($pdf->load_all_content());


#Apenas os válidos
#print_r($pdf->load_all_content(1));

#Apenas os inválidos
print_r($pdf->load_all_content(2));
