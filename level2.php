<?php
/*
------------------------------------------------------------
			      LEVEL2
	           Comparazione nomi strade fra 
		  OpenData stradario di Livorno
		   		e 
		    set di dati OpenStreetMap

         Author: F. Carrai, Gruppo Utenti Linux Livorno

			  License: CC-BY

Versioni:
v1.0 16-Feb-2015 FC	Originale
v1.1 19-Feb-2015 FC	Espansione acronimi
v1.2 03-Mar-2015 FC	Bug nel "full match": il numero delle
			parole comuni deve essere uguale al
			numero di parole di entrambe le strade
			(A. Margelli)
------------------------------------------------------------
*/

function AcronymExpansion(&$x)
{
	$DUG = array(	"v.le" => "viale",
			"v." => "via",
			"l.go" => "largo",
			"p.za" => "piazza");

	for ($i=0; $i<sizeof($x)-1; $i++)
	{
		if (array_key_exists($x[$i], $DUG))
		{
			echo "DUG expanded from ",$x[$i], " to ", $DUG[$x[$i]], "\n";
			$x[$i] = $DUG[$x[$i]];
		}
	}
}

function RemoveDUGs(&$x)
{
	$DUG = array("via", "viale","borgo",
	 	     "dei", "di", "delle", "dalle","della","del","de","a",
		     "vicolo","piazza","largo","dello","don","san","e");

	foreach ($DUG AS $item)
		if(($key = array_search($item, $x)) !== false) {
		    unset($x[$key]);
		}

}

//--- Leggi dati Comune di Livorno
$file = fopen('nomicomune.csv','r');
while (($line = fgetcsv($file)) != FALSE) {
	//print_r($line);
	//echo $line[0] ,"\t", $line[1], "\n";
	$comune[]=$line;
}
fclose($file);

//--Leggi dati OpenStreetMap
$file = fopen('nomiosm.csv','r');
while (($line = fgetcsv($file)) != FALSE) {
	//print_r($line);
	//echo $line[0] ,"\t",  $line[1], "\n";
	$osm[]=$line;
}
fclose($file);

// Cancella l'header letto dal file del comune e compatta
unset($comune[0]);
$comune=array_values($comune);

//-- Caricamento completato
echo "Caricati ",sizeof($comune), " indirizzi dal Comune di Livorno\n";
echo "Caricati ",sizeof($osm), " indirizzi da OpenStreetMap\n";

echo "Prima strada  : ",$comune[0][0], "\t", $comune[0][1], "\n";
echo "Ultima strada : ",$comune[sizeof($comune)-1][0], "\t", $comune[sizeof($comune)-1][1], "\n";

foreach ($osm as $a)
	$osmcompr[]=$a[1];
$osmcompr=array_unique($osmcompr);
$osmcompr=array_values($osmcompr);

echo "Rimossi duplicati. Rimasti ", sizeof($osmcompr), " indirizzi OSM\n";

//-- Ricerca dei full match...

echo "Full match\n";
echo "==========\n";

$fout=fopen("fullmatch.csv","w+");

$i=0;
$n=0;
foreach ($comune as $key_c => &$a)
{
	$parole_c = explode(" ", strtolower($a[1]));

	// Cerca in tutti gli indirizzi OSM
	$j=0;
	foreach ($osmcompr as $key_o => &$b)
	{
		$b=strtolower($b);

		$parole_o = explode(" ", $b);

		$inter = array_intersect ($parole_o, $parole_c);

		// Full match
		if ((sizeof($inter)==sizeof($parole_o)) && (sizeof($parole_o)==sizeof($parole_c)))
		{
			$n++;
			echo "Trovato ", $a[1],"\n";
			fwrite($fout, $a[1]."\n");
			unset($comune[$key_c]);
			unset($osmcompr[$key_o]);
			break;
		}
		$j++;
	}

	$i++;
}

fclose($fout);

echo "Trovate ",$n, " corrispondenze complete.\n";
$comune=array_values($comune);
echo "Rimangono ancora da trovare ",sizeof($comune), " indirizzi\n";

$osmcompr=array_values($osmcompr);
echo "fra ",sizeof($osmcompr)," indirizzi OSM\n";

//-- Analisi dei partial match...

echo "Match parziali\n";
echo "==============\n";

$fout=fopen("partmatch.csv","w+");
fwrite($fout,"Comune;OSM;Score\n");

$i=0;
foreach ($comune as $a)
{
	$parole_c = explode(" ", strtolower($a[1]));

	// Cerca in tutti gli indirizzi OSM
	$j=0;
	foreach ($osmcompr as $b)
	{
		$b=strtolower($b);

		$parole_o = explode(" ", $b);

		//-- HACK HERE!!! --//

		AcronymExpansion($parole_c);
		$inter = array_intersect ($parole_o, $parole_c);

		// Cancella match di parole comuni

		RemoveDUGs($inter);

		// La dimensione di $inter è il numero delle parole trovate

		if (sizeof($inter)>0)
		{
			echo $a[1]," - Trovato match parziale con ",$b,"\n";
			print_r ($inter);

			$score = sizeof($inter)/max(sizeof($parole_o), sizeof($parole_c));
			fwrite($fout, $a[1].";".$b.";" . number_format($score,2) . "\n");

			$j++; // Number of possible found matches
		}

		//-- STOP HACKING!!! --//
	}

	if ($j==0)
	{
		echo "No possible match found for ",$a[1],"\n";
		fwrite($fout, $a[1].";"."\n");
	}

	$i++;
}

fclose($fout);

$comune=array_values($comune);

?>
