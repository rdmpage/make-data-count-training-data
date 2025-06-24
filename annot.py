import os
import json
import csv
import re
import sys
import pymupdf

# 10.7717_peerj.13193 has same DOI in data availability and references cited, so not sure if primary of secondary

pdf_folder = 'PDF'

for filename in sorted(os.listdir(pdf_folder)):
    if filename.endswith('.pdf'):
        id = filename.replace('.pdf', '')
        
        print(id)
        
        pdf_path = os.path.join(pdf_folder, filename)
    
        doc = pymupdf.open(pdf_path) # open a document
        
        for page in doc:
            # Extract annotations from PDF
            annot = page.first_annot
            while annot:
                if annot.type[1] == "Highlight":
                    color = annot.colors.get("stroke")  # highlight color
                    
                    # get text, this can catch letters around the highlight, e.g. 10.21203_rs.3.rs-3338732_v1
                    quad_points = annot.vertices
                    quad_count = len(quad_points) // 4
                    for i in range(quad_count):
                        quad = quad_points[i*4:(i+1)*4]
                        rect = pymupdf.Quad(quad).rect
                        words = page.get_text("words", clip=rect)
                        text = " ".join(w[4] for w in sorted(words, key=lambda w: (w[1], w[0])))
                        print(f"Highlight: {text} : {color}")
                annot = annot.next
        
        print("\n")
