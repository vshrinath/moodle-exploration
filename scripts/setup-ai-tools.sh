#!/bin/bash
# Setup cross-tool AI assistant compatibility
# Creates symlinks so all AI tools read from AGENTS.md as the canonical source

set -e

echo "Setting up AI tool instruction files..."

# GitHub Copilot
mkdir -p .github
ln -sf ../AGENTS.md .github/copilot-instructions.md
echo "✓ GitHub Copilot: .github/copilot-instructions.md → AGENTS.md"

# Cursor
mkdir -p .cursor
ln -sf ../AGENTS.md .cursor/rules.md
echo "✓ Cursor: .cursor/rules.md → AGENTS.md"

# Cursor (alternate location)
ln -sf AGENTS.md .cursorules
echo "✓ Cursor (alt): .cursorules → AGENTS.md"

# Windsurf (Codeium)
ln -sf AGENTS.md .windsurfrules
echo "✓ Windsurf: .windsurfrules → AGENTS.md"

# Gemini CLI
ln -sf AGENTS.md GEMINI.md
echo "✓ Gemini CLI: GEMINI.md → AGENTS.md"

# Claude Code (for compatibility)
ln -sf AGENTS.md CLAUDE.md
echo "✓ Claude Code: CLAUDE.md → AGENTS.md"

# Kiro (symlink to avoid duplication)
mkdir -p .kiro/steering
ln -sf ../../AGENTS.md .kiro/steering/agents.md
echo "✓ Kiro: .kiro/steering/agents.md → AGENTS.md"

echo ""
echo "All AI tools now read from AGENTS.md as the canonical source."
echo "To update instructions, edit AGENTS.md only."
echo ""

# Verification check
echo "Verifying required files..."
[ -f "AGENTS.md" ] && echo "✓ AGENTS.md" || echo "✗ AGENTS.md - MISSING"
[ -f "CONVENTIONS.md" ] && echo "✓ CONVENTIONS.md" || echo "✗ CONVENTIONS.md - MISSING"
[ -d "skills" ] && echo "✓ skills/" || echo "✗ skills/ - MISSING"
[ -f "skills/coding/arch.md" ] && echo "✓ skills/coding/arch.md" || echo "✗ skills/coding/arch.md - MISSING"
[ -f "skills/coding/dev.md" ] && echo "✓ skills/coding/dev.md" || echo "✗ skills/coding/dev.md - MISSING"
[ -f "skills/coding/guard.md" ] && echo "✓ skills/coding/guard.md" || echo "✗ skills/coding/guard.md - MISSING"
[ -f "skills/coding/qa.md" ] && echo "✓ skills/coding/qa.md" || echo "✗ skills/coding/qa.md - MISSING"
[ -f "skills/design/ux.md" ] && echo "✓ skills/design/ux.md" || echo "✗ skills/design/ux.md - MISSING"
[ -f "skills/product/pm.md" ] && echo "✓ skills/product/pm.md" || echo "✗ skills/product/pm.md - MISSING"
[ -f "skills/ops/ops.md" ] && echo "✓ skills/ops/ops.md" || echo "✗ skills/ops/ops.md - MISSING"
[ -f "skills/marketing/video.md" ] && echo "✓ skills/marketing/video.md" || echo "✗ skills/marketing/video.md - MISSING"
[ -f "skills/marketing/writer.md" ] && echo "✓ skills/marketing/writer.md" || echo "✗ skills/marketing/writer.md - MISSING"
[ -f "skills/marketing/seo.md" ] && echo "✓ skills/marketing/seo.md" || echo "✗ skills/marketing/seo.md - MISSING"
[ -f "skills/marketing/perf.md" ] && echo "✓ skills/marketing/perf.md" || echo "✗ skills/marketing/perf.md - MISSING"
[ -f "brand/brand.md" ] && echo "✓ brand/brand.md" || echo "✗ brand/brand.md - MISSING"
[ -d "brand/assets" ] && echo "✓ brand/assets/" || echo "✗ brand/assets/ - MISSING"
