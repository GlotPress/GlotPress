#!/bin/bash
# This script generates markdown-formatted release notes for GlotPress.
# It finds the latest git tag, lists commits since that tag (excluding merges),
# extracts PR numbers and commit descriptions, builds a "Recent Changes" section
# and a "PR URLs" reference list suitable for inclusion in a release.
# The script also prints the same summary information to the terminal.
echo "Generating release notes: finding latest tag, listing commits since that tag,"
echo -e "extracting PR numbers and descriptions, and building markdown output.\n"

# Run from the repository root, so the script works from any directory.
cd "$(dirname "$0")/.." || exit 1

# Get the latest tag
if ! latest_tag=$(git describe --tags --abbrev=0 2>/dev/null); then
  echo "ERROR: No tags found in this repository. Cannot determine the latest release." >&2
  exit 1
fi

# Get the commits since the latest tag
commits=$(git log --oneline --no-merges "$latest_tag"..HEAD)

# Initialize the markdown text and PR URLs array
markdown="## Recent Changes"$'\n\n'
pr_urls=()

# Iterate over each commit and collect PRs (skip commits without PR numbers)
while IFS= read -r commit; do
  # Extract the PR number from the trailing "(#1234)" that GitHub appends on
  # merge, ignoring issue references elsewhere in the commit subject.
  pr_number=$(echo "$commit" | grep -oE '\(#[0-9]+\)$' | tr -d '(#)')
  # If no PR number was found, skip this commit
  if [ -z "$pr_number" ]; then
    continue
  fi
  description=$(echo "$commit" | cut -d' ' -f2-)

  # Generate the markdown text with PR link
  markdown+="* ${description%" (#$pr_number)"}"
  markdown+=" ([#$pr_number])"$'\n'

  # Generate the PR URL and add it to the array
  pr_url="https://github.com/GlotPress/GlotPress/pull/$pr_number"
  pr_urls+=("[#$pr_number]: $pr_url")
done <<< "$commits"

# If we didn't find any PRs, print a warning and finish
if [ ${#pr_urls[@]} -eq 0 ]; then
  echo "WARNING: No new pull requests found since tag '$latest_tag'. Nothing to release."
  exit 0
fi

# Add PR URLs section to the bottom of the markdown text
markdown+=$'\n'"---"$'\n\n'"## PR URLs"
for url in "${pr_urls[@]}"; do
  markdown+=$'\n'"$url"
done

# Print the final markdown text
echo "$markdown"
