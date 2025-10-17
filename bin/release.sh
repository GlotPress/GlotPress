#!/bin/bash
# This script generates markdown-formatted release notes for GlotPress.
# It finds the latest git tag, lists commits since that tag (excluding merges),
# extracts PR numbers and commit descriptions, builds a "Recent Changes" section
# and a "PR URLs" reference list suitable for inclusion in a release.
# The script also prints the same summary information to the terminal.
echo "Generating release notes: finding latest tag, listing commits since that tag,"
echo "extracting PR numbers and descriptions, and building markdown output."
cd ..

# Get the latest tag
latest_tag=$(git describe --tags --abbrev=0)

# Get the commits since the latest tag
commits=$(git log --oneline --no-merges $latest_tag..HEAD)

# Initialize the markdown text and PR URLs array
markdown="## Recent Changes"$'\n\n'
pr_urls=()

# Iterate over each commit
while IFS= read -r commit; do
  # Extract the PR number and description from the commit message
  pr_number=$(echo "$commit" | grep -o '#[0-9]*' | cut -c2-)
  description=$(echo "$commit" | cut -d' ' -f2-)

  # Generate the markdown text with PR link
  markdown+="* ${description/ \(#$pr_number\)}"
  markdown+=" ([#$pr_number])"$'\n'

  # Generate the PR URL and add it to the array
  pr_url="https://github.com/GlotPress/GlotPress/pull/$pr_number"
  pr_urls+=("[#$pr_number]: $pr_url")
done <<< "$commits"

# Add PR URLs section to the bottom of the markdown text
markdown+=$'\n'"---"$'\n\n'"## PR URLs"
for url in "${pr_urls[@]}"; do
  markdown+=$'\n'"$url"
done

# Print the final markdown text
echo "$markdown"
