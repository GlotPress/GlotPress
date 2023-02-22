MAKEFILE_PATH := $(dir $(lastword $(MAKEFILE_LIST)))
NODE_PATH := $(MAKEFILE_PATH)test,$(MAKEFILE_PATH):$(NODE_PATH)
REPORTER ?= spec
MOCHA ?= ./node_modules/.bin/mocha
BROWSERIFY = ./node_modules/.bin/browserify
UGLIFYJS = ./node_modules/.bin/uglifyjs
FLAGS = -t uglifyify


all: inline-translation.js inline-translation.min.js inline-translation.css

inline-translation.js: browserify

inline-translation.min.js: uglifyjs

browserify:
	$(BROWSERIFY) $(FLAGS) lib/index.js --standalone gpInlineTranslation -o inline-translation.js

uglifyjs:
	$(UGLIFYJS) inline-translation.js -c > inline-translation.min.js

inline-translation.css: css/custom.css
	cat css/jquery.webui*.css css/custom.css > inline-translation.css

test:
	@NODE_PATH=$(NODE_PATH) $(MOCHA) --reporter $(REPORTER) test/index.js

install: all
	cp inline-translation.min.js ../assets/js
	cp inline-translation.css ../assets/css

.PHONY: test
