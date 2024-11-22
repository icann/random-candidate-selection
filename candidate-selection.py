#!/usr/bin/env python3

import hashlib, sys
from pathlib import Path

# Program to randomly select some candidates from a group
# The program takes one or two arguments.
#   The first argument is the file that lists the candidate names
#     in UTF-8, one name per line
#   If there is a second argument, it is the file that says how
#     many candidates are selected, and the public random input value
#   If only the first argument is given, the program just prints out
#     the hash values for each name.

# Helper function to turn a UTF-8 string into its hex representation
def hexify(in_str):
  return "".join([hex(c)[2:] for c in in_str.encode("utf8")])

# Sanity check the input files given on the command line
if len(sys.argv) == 1:
  exit("Must give the name of the candidate file, and possibly " + \
    "the selection file, on the command line. Exiting.")
candidate_path = Path(sys.argv[1])
if not candidate_path.exists():
  exit(f"The file {str(candidate_path)} doesn't exist. Exiting.")
try:
  candidate_f = candidate_path.open(mode="rt", encoding="utf8")
except:
  exit("The candidates file doesn't appear to be in UTF-8. Exiting.")
candidate_lines = candidate_f.read().splitlines()
# See if there is a second file for selecting
if len(sys.argv) == 3:
  run_including_selection = True
  selection_path = Path(sys.argv[2])
  if not selection_path.exists():
    exit(f"The file {str(selection_path)} doesn't exist. Exiting.")
  try:
    selection_f = selection_path.open(mode="rt", encoding="utf8")
  except:
    exit("The selection file doesn't appear to be UTF-8. Exiting.")
  selection_lines = selection_f.read().splitlines()
  # Extract D and S from the selection file
  S_str = selection_lines[0]
  try:
    S = int(S_str)
  except:
    print(f"The first line of the selection file, '{S_str}', " + \
      "is not an integer. Exiting.")
  # D_str is the string for D, D_hex is the hex version for display
  D_str = selection_lines[1]
  D_hex = hexify(D_str)
else:
  run_including_selection = False

# Get the candidates information
C_info = []
for C_str in candidate_lines:
  C_hex = hexify(C_str)
  if run_including_selection:
    C_with_D_str = C_str + D_str
    C_with_D_hex = hexify(C_with_D_str)
    C_with_D_hash = hashlib.sha256(C_with_D_hex.encode("utf-8"))
    C_info.append([C_str, C_hex, C_with_D_str, C_with_D_hex, \
      C_with_D_hash.hexdigest()])
  else:
    C_info.append([C_str, C_hex])

# Print the results
if run_including_selection:
  print(f"S is {S}")
  print(f"D is \"{D_str}\"")
  print(f" {D_hex}\n")
  print("Candidate information, sorted by hash of name including D")
  selected = []
  # Sort by the hex of C_with_D_hash
  for this_info in sorted(C_info, key=lambda a: a[4], reverse=True):
    # Decrement S for each name that is selected
    if S > 0:
      selected.append(this_info[0])
      S -= 1
    print(f"{this_info[2]}")
    print(f" {this_info[3]}")
    print(f" {this_info[4]}")
  print("\nSelected:\n    " + "\n    ".join(selected))
else:
  for this_info in C_info:
    print(f"{this_info[0]}")
    print(f" {this_info[1]}")
