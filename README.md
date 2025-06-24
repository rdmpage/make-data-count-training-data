# Alternative scoring for Make Data Count Kaggle Competition

This is a manual scoring of the [Make Data Count - Finding Data References](https://www.kaggle.com/competitions/make-data-count-finding-data-references/overview) Kaggle competition.

The training data for this competition comprises 524 PDF files and 404 XML files. This repository contains the original training data file `train_labels.csv` which list the name of each article, and the data sets (if any) that are cited by that article. The citations are split into two categories, “Primary” (data created for the work reported in the article) and “Secondary” (previously created data reused by that work).

## Manually labelling the PDFs

The `train_labels.csv` provided for the competition is riddled with errors, so I manually looked at each PDF and highlighted strings that look like dataset identifiers (such as DOIs, GenBank accessions, etc.).

| Colour | Meaning |
|--|--|
| 🟧 | Primary data citation |
| 🟨 | Secondary data citation |
| 🟩 | Other string, such as grant number |

Some identifiers such as DOIs pose a problem as they may span more than one line. The PDF annotation for the highlight is split into one rectangular regions per line, so the DOI may be dtsributed over two boxes. To minimise this, if a DOI spanned two lines, but everything after the `10.*` was on the second line, only that part was highlighted.

## Extracting markup

Once the PDFs were marked up, a Python script was used to extract the highlights as PDF annotations (the script uses [PyMuPDF](https://pymupdf.readthedocs.io/en/latest/). Extracting text annotations can be tricky as PDF annotations are defined by a bounding boxe, and the box can overlap adjacent characters. hence we need to do some cleaning before outputting the annotations. We run the script in a Python environment:

```
python3 -m venv .venv 
source .venv/bin/activate
pip install pymupdf

python annot.py > annot.txt
```

The output is then run through a PHP script to convert the file `annot.txt` into a CSV file that contains the new training data `new_training_labels.csv`. The script cleans off extraneous characters, and joins together identifiers that span multiple lines (e.g., DOIs), and cleans DOIs. This script is not perfect so there will be errors in the new labels.







