<?php

//----------------------------------------------------------------------------------------
// http://stackoverflow.com/a/5996888/9684
function translate_quoted($string) {
  $search  = array("\\t", "\\n", "\\r");
  $replace = array( "\t",  "\n",  "\r");
  return str_replace($search, $replace, $string);
}

//----------------------------------------------------------------------------------------
function read_data($filename)
{
	$data = array();
	
	$headings = array();
	
	$row_count = 0;
	
	$file = @fopen($filename, "r") or die("couldn't open $filename");
			
	$file_handle = fopen($filename, "r");
	while (!feof($file_handle)) 
	{
		$row = fgetcsv(
			$file_handle, 
			0, 
			translate_quoted(','),
			translate_quoted('"') 
			);
			
		$go = is_array($row);
		
		if ($go)
		{
			if ($row_count == 0)
			{
				$headings = $row;		
			}
			else
			{
				$obj = new stdclass;
			
				foreach ($row as $k => $v)
				{
					if ($v != '')
					{
						$obj->{$headings[$k]} = $v;
					}
				}
			
				//print_r($obj);	
				
				if ($obj->dataset_id != "Missing")
				{
					if (!isset($data[$obj->article_id]))
					{
						$data[$obj->article_id] = array();
					}
					$data[$obj->article_id][$obj->dataset_id] = $obj->type;
					
					if (!isset($obj->type))
					{
						print_r($obj);
						exit();
					}
				}
			}
		}	
		$row_count++;
	}	
	
	return $data;
}

        $patterns = array(
            'arxe'      => 'E-GEOD-\d+', // https://www.ebi.ac.uk/biostudies/arrayexpress
            'arxp'      => 'E-PROT-\d+', // https://www.ebi.ac.uk/biostudies/arrayexpress
            'biosample' => 'SAM[NED]\w?\d+', // https://registry.identifiers.org/registry/biosample
            
            'cellosaurus' => '(CVCL_[0-9A-Z][0-9A-Z]\d{2})',
            
            'chembl'    => 'CHEMBL\d+',
            
            'dbsnp'     => 'rs\d{4,}', // modified from https://registry.identifiers.org/registry/dbsnp
            
            //'dra'       => 'DRA\d{6}', // https://www.ddbj.nig.ac.jp/dra/index-e.html
            
            'empiar'    => 'EMPIAR-\d{5,}',
            
            'encode'    => 'ENCSR[A-Z0-9]+', // ENCODE 
            
            'ensembl'   => 'ENS[A-Z]{4}\d{11}',   // ENSBTAG00000011038
            
            'insdcgca'  => '(GCA_[0-9]{9}(\.[0-9]+)?)', // insdc.gca
            
            // https://www.ncbi.nlm.nih.gov/genbank/acc_prefix/
            //'genbank'   => '\b([A-Z]\d{5}|[A-Z]{2}\d{6})\b',
            'genbank'   => '\b([A-Z]{2}\d{6})\b', // just 2 letters + 6 digits
            
            'gisaidisl' => '(EPI(_ISL_)?\d+)', // not in identifiers.org
            
            'geo'       => 'GSM\d{5,}', // modified https://registry.identifiers.org/registry/geo
            
            //'massive'   => 'MSV\d{9}', // https://massive.ucsd.edu/
            
            // https://www.ncbi.nlm.nih.gov/books/NBK21091/table/ch18.T.refseq_accession_numbers_and_mole/?report=objectonly
            'nm'        => '(N[CM]_\d{6}(\.[0-9]+)?)', 
            
            'gse'       => '((GEO:\s*)?GSE\d{5,})',
            
            'hpa'       => '((CAB|HPA)\d{6})', // http://www.proteinatlas.org/search/CAB004592
            'interpro'  => 'IPR\d{6}',
            
            'pdb'     => '\b(PDB:\s*[0-9][A-Za-z0-9]{3})\b', // PDB, likely lots of false hits unless we include prefix
            
            'pfam'      => '(PF\d{5}(.\d{1,2})?)', // PFAM seems to have versions, e.g. PF01493.23)
            'prjna'     => 'PRJ[CDEN][A-Z]\d+', // https://registry.identifiers.org/registry/bioproject
            'pxd'       => 'PXD\d{6}', // https://www.proteomexchange.org    
            'sra'       => '[SED]R[APRSXZ]\d+', // https://registry.identifiers.org/registry/insdc.sra
            
            'up'        => 'UP\d{9}', // https://www.uniprot.org/proteomes/UP000006548
        );     



$filename = 'new_training_labels.csv';

$data = read_data($filename);

//print_r($data);

$dois     = array();
$datasets = array();

$unknown = array();


foreach ($data as $article_id  => $citations)
{
	foreach ($citations as $dataset_id => $type)
	{
		echo $dataset_id . "\n";
		
		if (preg_match('/^https:\/\/doi.org\/(10.\d+)\//', $dataset_id, $m))
		{
			$prefix = $m[1];
			if (!isset($dois[$prefix]))
			{
				$dois[$prefix] = array('Primary' => 0, 'Secondary' => 0);
			}
			$dois[$prefix][$type]++;
		}
		else
		{
			// classify identifier type
			
			$data_type = 'unknown';
			
			$matched = false;
			
			foreach ($patterns as $db => $pattern)
			{
				if (preg_match('/' . $pattern . '/', $dataset_id))
				{
					$data_type = $db;
					$matched = true;
					
					echo "$dataset_id $db $pattern\n";
				}
				
				if ($matched == true) break;
			
			}
			
			if ($data_type == 'unknown')
			{
				$unknown[] = $dataset_id;
			}
			
			
			if (!isset($datasets[$data_type]))
			{
				$datasets[$data_type] = array('Primary' => 0, 'Secondary' => 0);			
			}
			$datasets[$data_type][$type]++;
		
		
		}
	}
}

//print_r($unknown);
//exit();


print_r($dois);



print_r($datasets);

ksort($datasets);

foreach ($datasets as $db => $types)
{
	echo $db . ' ' . round($types['Primary'] / ($types['Primary'] + $types['Secondary']),2) . "\n";
}



/*
print_r($dois);

foreach ($dois as $prefix => $types)
{
	echo $prefix . ' ' . ($types['Primary'] + $types['Secondary']) . ' ' . round($types['Primary'] / ($types['Primary'] + $types['Secondary']),2) . "\n";
}
*/


?>



