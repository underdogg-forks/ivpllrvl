#!/bin/bash

# This script helps identify patterns in test files for migration

echo "=== Patterns to migrate in Core test files ==="
echo ""
echo "1. Direct controller calls pattern:"
grep -n '\$controller = \$this->getController()' modules/core/tests/*.php | head -20
echo ""
echo "2. ob_start/ob_get_clean pattern:"
grep -n 'ob_start()' modules/core/tests/*.php | head -15
echo ""
echo "3. setPostData calls:"
grep -n 'setPostData' modules/core/tests/*.php | head -15
echo ""
echo "4. assertResponse patterns:"
grep -n 'assertResponse' modules/core/tests/*.php | head -15
