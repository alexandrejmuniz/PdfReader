<?php

include ( 'src/PdfParser.php' ) ;

#dados do banco
$pdf = new PdfParser( 'localhost','root','','pdftotext' );

$palavra_desejada = 'alunos';

# Caso você queira deixar o texto com uma classe especifica pra busca
print_r($pdf->search_all_content($palavra_desejada,'<span class="search">','</span>',200));

# Caso você queira deixar o texto apenas em negrito
print_r($pdf->search_all_content($palavra_desejada,'<strong>','</strong>',200));

