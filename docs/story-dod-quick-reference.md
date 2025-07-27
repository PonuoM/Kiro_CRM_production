# Story DoD Quick Reference Guide

## 🚀 Quick Start

### For Developers
1. **Before Starting**: Review `/docs/story-dod-checklist.md`
2. **During Development**: Use checklist sections as implementation guide
3. **Before Completion**: Run `php validate_story_dod.php --story=X.Y`
4. **Final Check**: Ensure score ≥85% before marking "Ready for Review"

### For QA/Reviewers
1. **Story Review**: Check completion report exists
2. **Validation**: Run DoD validation script
3. **Quality Gates**: Verify all critical criteria met
4. **Sign-off**: Use sign-off template in main DoD document

---

## 📊 Validation Commands

```bash
# Basic validation
php validate_story_dod.php --story=1.2

# Detailed analysis
php validate_story_dod.php --story=1.2 --detailed

# Auto-fix common issues
php validate_story_dod.php --story=1.2 --fix

# Help and examples
php validate_story_dod.php --help
```

## 🎯 Quality Thresholds

| Score | Status | Action Required |
|-------|--------|----------------|
| 95%+ | 🎉 EXCELLENT | Production ready |
| 85-94% | ✅ GOOD | Minor fixes needed |
| 70-84% | ⚠️ NEEDS WORK | Several issues to address |
| <70% | ❌ NOT READY | Major rework required |

---

## 📋 Essential Checklist Items

### 🔴 Critical (Must Pass)
- [ ] All acceptance criteria validated
- [ ] Security requirements met
- [ ] Database integrity maintained
- [ ] No regression issues

### 🟡 Important (Should Pass)
- [ ] Test coverage ≥80%
- [ ] Performance benchmarks met
- [ ] Documentation complete
- [ ] Error handling implemented

### 🟢 Good to Have
- [ ] Code optimization
- [ ] Advanced monitoring
- [ ] Extended test coverage
- [ ] Comprehensive documentation

---

## 🛠️ Common Fix Actions

### Missing Documentation
```bash
# Add to story file
## QA Results
*Results from QA Agent review*

## Dev Agent Record  
*Implementation details*
```

### Missing Tests
```bash
# Create test file
mkdir -p tests/[category]
touch tests/[category]/test_[story_id].php
```

### Security Issues
```bash
# Check for common patterns
grep -r "mysql_query" . --include="*.php"
grep -r "echo \$_" . --include="*.php"
```

### Performance Issues
```bash
# Add LIMIT clauses
grep -r "SELECT.*FROM" . --include="*.php" | grep -v "LIMIT"
```

---

## 📁 File Organization

### Required Files per Story
```
docs/stories/[X.Y].story.md          # Main story file
story_[X.Y]_completion_report.md     # Completion report
tests/*/test_[story_id].php           # Test files
validate_[story_id].php               # Validation script (optional)
```

### Documentation Structure
```
## Status
✅ Ready for Review / COMPLETED

## Story
**As a** [role], **I want** [goal], **so that** [benefit]

## Acceptance Criteria
1. [Specific requirement]
2. [Specific requirement]

## Tasks / Subtasks
- [x] Task 1: [Description]
  - [x] Subtask 1.1
  - [x] Subtask 1.2

## Dev Agent Record
*Implementation details*

## QA Results
*QA review results*
```

---

## 🔍 Validation Categories

1. **📋 Requirements** - Story analysis, AC definition, task completion
2. **⚡ Technical** - Implementation quality, security, performance  
3. **📚 Documentation** - Story docs, completion reports, guides
4. **🧪 Testing** - Unit tests, integration tests, validation scripts
5. **🚀 Production** - Migration scripts, deployment guides, backups
6. **🏆 Quality** - QA review, completion status, file documentation
7. **🔗 Integration** - System compatibility, API compliance, structure
8. **✅ Completeness** - Overall completeness and evidence

---

## 🎨 Status Indicators

### Story Status
- `Ready for Review` - Implementation complete, ready for QA
- `✅ COMPLETED` - QA approved, production ready
- `In Progress` - Currently being developed
- `Pending` - Waiting to start

### DoD Validation
- `✅` - Criterion passed
- `❌` - Criterion failed  
- `⚠️` - Warning/optional item
- `ℹ️` - Information/note

---

## 🚨 Emergency Procedures

### Critical Issue Found
1. **Stop deployment** immediately
2. **Mark story** as "Needs Rework"
3. **Document issue** in story file
4. **Create hotfix** story if needed
5. **Re-validate** after fixes

### Rollback Required
1. **Use rollback scripts** in `/database/rollback_*.sql`
2. **Restore from backup** if needed
3. **Document incident** in completion report
4. **Update DoD checklist** with lessons learned

---

## 📞 Support & Resources

### Documentation
- Main DoD Checklist: `/docs/story-dod-checklist.md`
- Architecture Guide: `/docs/architecture.md`
- PRD Requirements: `/docs/prd.md`

### Tools
- Validation Script: `validate_story_dod.php`
- Demo Output: `demo_story_dod_validation.php`
- Health Check: `pages/checklist.php`

### Example Stories
- Story 1.1: Database schema changes
- Story 1.2: Cron job development
- Story 1.3: API development

---

*This quick reference is maintained alongside the main DoD checklist and should be updated as processes evolve.*