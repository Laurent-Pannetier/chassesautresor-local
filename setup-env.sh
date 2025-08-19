#!/usr/bin/env bash
# Discover the system PHP binary before adjusting PATH so that project
# tooling can rely on a working interpreter.
export PHP_BIN="$(command -v php)"
export PATH="$PWD/bin:$PATH"
