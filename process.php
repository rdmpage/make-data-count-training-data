<?php

$filename = "annot.txt";

function clean_doi($doi)
{
	$doi = preg_replace('/[;|,|\.|\)|>|\]]+$/', '', $doi);
	
	$doi = preg_replace('/^([^ ]{1,2}[ ])+/', '', $doi);	
	
	$doi = preg_replace('/^\//', '', $doi);	
	
	
	$doi = preg_replace('/^DOI[:|,]\s*/i', '', $doi);
	$doi = preg_replace('/^https?:\/\/(dx\.)?doi.org\//', '', $doi);
	$doi = preg_replace('/^https?:\/\/doi.pangaea\.de\//', '', $doi);
	$doi = preg_replace('/^https:\/\/datadryad.org\/stash\/dataset\/doi:\//', '', $doi);
	$doi = preg_replace('/#.*$/', '', $doi);
	$doi = strtolower($doi);
	$doi = 'https://doi.org/' . $doi;
    
	return $doi;
}

$data = array();


$id = '';
$state = 0;

$count = 1;

$file_handle = fopen($filename, "r");
while (!feof($file_handle)) 
{
	$line = trim(fgets($file_handle));
	echo "[$count] $state |" . $line . "|\n";
	
	$value = '';
	$colour = '';

	if (preg_match('/Highlight:\s+(?<value>.*)\s+:\s+(?<colour>\[((\d+\.\d+)(,\s+)?)+\])/', $line, $m))
	{
		$value = $m['value'];
		
		switch ($m['colour'])
		{
			case '[0.0, 1.0, 0.0]':
				$colour = 'green';
				break;

			case '[1.0, 0.5, 0.0]':
				$colour = 'orange';
				break;

			case '[1.0, 1.0, 0.0]':
				$colour = 'yellow';
				break;		
		}
	}
	
	switch ($state)
	{
		case 0:
			if (preg_match('/^10/', $line))
			{
				$id = $line;
				
				$data[$id] = array();
				
				$state = 1;
			}
			break;
			
		case 1:
			if ($colour == 'orange')
			{
				echo $value . "|Primary\n";
				
				$data[$id][$value] = 'Primary';
				
			}
			if ($colour == 'yellow')
			{
				echo $value . "|Secondary\n";
				
				$data[$id][$value] = 'Secondary';
			}
			if ($line == '')
			{
				$state = 0;
			}
			break;
			
	
		default:
			break;
	}
	
	if ($count++ > 1000)
	{
		//break;
	}

}

// merge split DOIs (and other identifiers)
if (1)
{
	foreach ($data as $article_id => &$values)
	{
		$ids = array_keys($values);
		
		print_r($ids);
		
		$n = count($ids);
		
		if ($n > 1)
		{
			$i = 0;
			while ($i < $n - 1)
			{
				$merge = false;
				
				if (preg_match('/^(doi:)?10\.\d+\/$/', $ids[$i]))
				{
					$merge = true;
				}
	
				if (preg_match('/^10\.\d+\/[a-zA-Z0-9]+\.$/', $ids[$i]))
				{
					$merge = true;
				}
	
				if (preg_match('/^10\.$/', $ids[$i]))
				{
					$merge = true;
				}
				
				if (preg_match('/^http:\/\/dx.doi\.$/', $ids[$i]))
				{
					$merge = true;
				}
	
				if (preg_match('/^https?:\/\/doi.org\/$/', $ids[$i]))
				{
					$merge = true;
				}
	
				if (preg_match('/^https?:\/\/dx.doi.org\/10\.$/', $ids[$i]))
				{
					$merge = true;
				}
				
				if (preg_match('/^https?:\/\/doi.org\/10.5061\/dryad\.$/', $ids[$i]))
				{
					$merge = true;
				}
				
				if ($merge)
				{
					$first = $i;
					$next = $i + 1;
					$i++;
					
					echo "Merging $ids[$first] and $ids[$next]\n";
					
					$merged_key = $ids[$first] . $ids[$next];
					$merged_value = $values[$ids[$first]];
					
					unset($values[$ids[$first]]);
					unset($values[$ids[$next]]);
					
					unset($ids[$first]);
					unset($ids[$next]);
	
					$values[$merged_key] = $merged_value;
				
				}
				
				$i++;		
			}
		}
		
		echo "Article id: $article_id \n";
		
		echo "Merged\n";
		print_r($ids);	
		
		echo "Values\n";
		print_r($values);
		
		echo "-----------\n\n";
		
		
	
	}
	
}

//exit();

// clean
$cleaned_data = array();

foreach($data as $article_id => &$values)
{
	if (count($values) > 0)
	{
		//echo $article_id . "\n";
		//print_r($values);
	
		$cleaned_values = array();
		$cleaned_data[$article_id] = [];
		
		foreach ($values as $dataset_id => $type)
		{
			if (preg_match('/(doi[:|\.]|10\.\d+)/', $dataset_id))
			{
				$dataset_id = clean_doi($dataset_id);
				//$dataset_id = $dataset_id;
			}
			else
			{				
				$dataset_id = preg_replace('/[\)|\.]$/', '', $dataset_id);
				$dataset_id = preg_replace('/^([^ ][ ])+/', '', $dataset_id);
				$dataset_id = preg_replace('/([ ][^ ])$/', '', $dataset_id);

				// (AENSOARG00000013966
				$dataset_id = preg_replace('/^\(A$/', '', $dataset_id);
				
				// )GCA_000165715.3
				// )h_GCA_007744255.1
				// )hgGCA_011634775.1
				$dataset_id = preg_replace('/^\)([a-z]+_)?/', '', $dataset_id);

				// GCA_001707835.1)
				// GCA_007747995.1)h
				$dataset_id = preg_replace('/\)[a-z]?$/', '', $dataset_id);
				
								
				// final clean			
				$dataset_id = preg_replace('/[,| ]/', '', $dataset_id);	
				
			}
			$cleaned_values[$dataset_id] = $type;
		}
		$cleaned_data[$article_id] = $cleaned_values;		
	}
}

echo "\nOutput\n\n";

$output = '';

//$output .= "row_id,article_id,dataset_id,type\n";
$output .= "article_id,dataset_id,type\n";

$counter = 0;

foreach ($cleaned_data as $article_id => $values)
{
	foreach ($values as $dataset_id => $type)
	{
		//$row = array($counter++, $article_id, $dataset_id, $type);
		$row = array($article_id, $dataset_id, $type);
		$output .= join(",", $row) . "\n";
	}
}

echo $output . "\n";

file_put_contents('new_training_labels.csv', $output);



