#!/usr/bin/env python3

# A program to randomly select some candidates from a group.
# The program takes one or two arguments.
#   The first argument is an input file that lists the candidate names,
#     one name per line. The file must be UTF-8 (which means that
#     all-ASCII is just fine too).
#   If there is a second argument, it is the file that says how
#     many candidates are to be selected, and the public random input
#     value. Those are given on two lines in the file
#   If only the first file is named, the program just prints out
#     the hex values for each name. This might be useful to be sure
#     that the input file is correct.

import hashlib, sys
from pathlib import Path

# Helper function to turn a UTF-8 string into its hex representation
def hexify(in_str):
  return "".join([hex(char)[2:] for char in in_str.encode("utf8")])

# Check that the command line contains at leat one file name
if len(sys.argv) == 1:
  exit("You must give the name of the candidate file, and " + \
    "possibly the selection file, on the command line. Exiting.")
candidate_path = Path(sys.argv[1])
if not candidate_path.exists():
  exit(f"The file {str(candidate_path)} doesn't exist. Exiting.")
try:
  candidate_f = candidate_path.open(mode="rt", encoding="utf8")
except:
  exit("The candidates file doesn't appear to be in UTF-8. Exiting.")
# The file exists and is in the right encoding, so get the names
#   from the lines in the file. Note that splitlines does not
#   keep the line ending.
candidate_lines = candidate_f.read().splitlines()

# run_including_selection is used later to determine what to print
run_including_selection = True if len(sys.argv) == 3 else False
if run_including_selection:
  selection_path = Path(sys.argv[2])
  if not selection_path.exists():
    exit(f"The file {str(selection_path)} doesn't exist. Exiting.")
  try:
    selection_f = selection_path.open(mode="rt", encoding="utf8")
  except:
    exit("The selection file doesn't appear to be UTF-8. Exiting.")
  # It is OK if the selection file has more than two lines; the rest
  #   are ignored
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

# Get the candidates information
C_info = []
for C_str in candidate_lines:
  C_hex = hexify(C_str)
  if run_including_selection:
    C_with_D_str = C_str + "/" + D_str
    C_with_D_hex = hexify(C_with_D_str)
    C_with_D_hash = hashlib.sha256(C_with_D_hex.encode("utf-8"))
    C_info.append([C_str, C_hex, C_with_D_str, C_with_D_hex, \
      C_with_D_hash.hexdigest()])
  else:
    C_info.append([C_str, C_hex])

# Print the results
if run_including_selection:
  header_to_print = f"S is {S}\n" + \
    f"D is '{D_str}'\n" + \
    f"Hex of D is '{D_hex}'\n" + \
    "The first line in the list is the name with D appended.\n" + \
    "The second line is the hash of that.\n" + \
    "The list is sorted by the hash values, descending.\n"
  print(header_to_print)
  selected = []
  # Sort by the hex of C_with_D_hash
  for this_info in sorted(C_info, key=lambda a: a[4], reverse=True):
    # Decrement S for each name that is selected
    if S > 0:
      selected.append(this_info[0])
      S -= 1
    print(f"{this_info[2]}\n  {this_info[3]}\n  {this_info[4]}")
  print("\nSelected:\n    " + "\n    ".join(selected))
else:
  for this_info in C_info:
    print(f"{this_info[0]}\n  {this_info[1]}")
