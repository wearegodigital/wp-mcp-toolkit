# WP MCP Toolkit — Build Commands
PLUGIN_SLUG := wp-mcp-toolkit
VERSION := $(shell grep 'Version:' wp-mcp-toolkit.php | head -1 | sed 's/.*Version:[[:space:]]*//')
DIST_DIR := /tmp
DIST_FILE := $(DIST_DIR)/$(PLUGIN_SLUG)-$(VERSION).zip
BUILD_DIR := $(DIST_DIR)/$(PLUGIN_SLUG)-build

.PHONY: dist clean lint check

## Build distribution zip (production-ready, excludes dev files)
dist:
	@echo "Building $(PLUGIN_SLUG) v$(VERSION)..."
	@echo "1. Copying plugin to clean build directory..."
	@rm -rf $(BUILD_DIR)
	@mkdir -p $(BUILD_DIR)/$(PLUGIN_SLUG)
	@rsync -a --exclude-from=.distignore . $(BUILD_DIR)/$(PLUGIN_SLUG)/
	@echo "2. Creating zip..."
	@rm -f $(DIST_FILE)
	@cd $(BUILD_DIR) && zip -rq $(DIST_FILE) $(PLUGIN_SLUG)/ -x "*.DS_Store"
	@rm -rf $(BUILD_DIR)
	@echo ""
	@echo "Done! Distribution zip:"
	@ls -lh $(DIST_FILE)
	@echo "Files: $$(unzip -l $(DIST_FILE) | tail -1)"

## Remove distribution zip
clean:
	rm -f $(DIST_DIR)/$(PLUGIN_SLUG)-*.zip
	rm -rf $(BUILD_DIR)

## Run PHP syntax check on all plugin files
lint:
	@find includes admin -name '*.php' | while read f; do \
		php -l "$$f" 2>&1 | grep -v "No syntax errors"; \
	done; \
	echo "Lint complete."

## Pre-release checks
check:
	@echo "=== Pre-release checks for v$(VERSION) ==="
	@echo ""
	@echo "Version strings:"
	@grep 'Version:' wp-mcp-toolkit.php | head -1
	@grep 'WP_MCP_VERSION' wp-mcp-toolkit.php
	@grep 'Stable tag:' readme.txt
	@echo ""
	@echo "Changelog:"
	@grep -c "= $(VERSION) =" readme.txt > /dev/null && echo "  Changelog entry: OK" || echo "  Changelog entry: MISSING"
	@echo ""
	@echo "Readme tags:"
	@TAGS=$$(grep '^Tags:' readme.txt | tr ',' '\n' | wc -l | tr -d ' '); \
		echo "  Tag count: $$TAGS (max 5)"; \
		[ "$$TAGS" -le 5 ] && echo "  Status: OK" || echo "  Status: TOO MANY"
	@echo ""
	@echo "Tested up to:"
	@TESTED=$$(grep 'Tested up to:' readme.txt | sed 's/.*: *//'); \
		echo "  Value: $$TESTED"; \
		echo "$$TESTED" | grep -qE '^[0-9]+\.[0-9]+$$' && echo "  Status: OK (major version only)" || echo "  Status: WARNING (should be major version only)"
