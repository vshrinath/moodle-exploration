# Mock Users Setup (Local Docker Moodle)

## Purpose

Reference for the mock accounts created for role-based dashboard/RBAC validation in the local Docker environment.

## Apply Workflow Simulation Baseline (Recommended)

Run this idempotent script to re-apply the WF-01 to WF-04 baseline in one step:

```bash
docker exec moodlehq-dev-moodle-1 php /var/www/html/public/scripts/config/configure_workflow_simulation_baseline.php --mode=local
```

What this script enforces:
- Mock users exist with stable unique `idnumber` values
- Required role assignments for sysadmin/trainer/learner
- Program Owner scoped to `allied-health` category (no system-level assignment)
- Program Owner workflow capabilities for course/quiz review and management
- `mock-allied-2026` cohort baseline with `mock.trainer` and `mock.learner`

### Production safety mode

Use real-environment verification mode to check prerequisites without mutating data:

```bash
docker exec moodlehq-dev-moodle-1 php /var/www/html/public/scripts/config/configure_workflow_simulation_baseline.php --mode=verify-real-env --dry-run --category-idnumber=allied-health
```

Apply real-environment role/capability baseline (no mock users/cohorts):

```bash
docker exec moodlehq-dev-moodle-1 php /var/www/html/public/scripts/config/configure_workflow_simulation_baseline.php --mode=apply-real-env --category-idnumber=allied-health
```

Apply real-environment baseline and assign specific Program Owners:

```bash
docker exec moodlehq-dev-moodle-1 php /var/www/html/public/scripts/config/configure_workflow_simulation_baseline.php --mode=apply-real-env --category-idnumber=allied-health --program-owner-usernames=owner1,owner2
```

Notes:
- `--mode=verify-real-env` is verify-only and skips mock-user/cohort mutations.
- `--mode=apply-real-env` applies only role/capability baseline and cache purge.
- `--dry-run` prints intended actions without applying changes.
- `--category-idnumber` is required in real-environment modes.
- `--program-owner-usernames` (optional in real-environment modes) assigns category-scoped `sceh_program_owner` and system-scoped `sceh_program_owner_competency`.

## Current Mock Users

| Username | Display Name | System Role Assignment | Expected Dashboard Role |
|---|---|---|---|
| `mock.sysadmin` | Mock System Admin | `sceh_system_admin` | System Admin |
| `mock.programowner` | Mock Program Owner | `sceh_program_owner` | Program Owner |
| `mock.trainer` | Mock Trainer | `sceh_trainer` | Trainer |
| `mock.learner` | Mock Learner | _None_ | Learner (fallback) |

## Notes on Passwords

- Passwords were generated randomly at creation time.
- Passwords are **not stored** in this repository.
- Each mock user has `forcepasswordchange=1`.

## How to Access These Accounts

1. Log in as site admin.
2. Use Moodle "Log in as" for each mock user:
   - `Site administration -> Users -> Permissions -> User policies` (ensure "Allow log in as" is enabled if needed)
   - Open user profile and click "Log in as"
3. Alternative: reset password from user profile/admin UI before direct login.

## Verify Role Assignments (CLI)

```bash
docker exec moodlehq-dev-moodle-1 php -r 'define("CLI_SCRIPT", true); require("/var/www/html/public/config.php"); global $DB; $users=["mock.sysadmin","mock.programowner","mock.trainer","mock.learner"]; foreach($users as $u){ $user=$DB->get_record("user",["username"=>$u,"deleted"=>0],"id,username",IGNORE_MISSING); if(!$user){ echo "MISSING\t$u\n"; continue; } echo "USER\t{$user->username}\n"; $sql="SELECT r.shortname FROM {role_assignments} ra JOIN {role} r ON r.id=ra.roleid WHERE ra.userid=:uid AND ra.contextid=1 ORDER BY r.shortname"; $roles=$DB->get_records_sql($sql,["uid"=>$user->id]); foreach($roles as $r){ echo "  ROLE\t{$r->shortname}\n"; } }'
```

## Sync System Admin Capabilities (CLI)

If `mock.sysadmin` dashboard cards open permission/missing-param errors, re-apply the expected capabilities:

```bash
docker exec -u 1:1 moodlehq-dev-moodle-1 php -r 'define("CLI_SCRIPT", true); require("/var/www/html/public/config.php"); require_once($CFG->libdir . "/accesslib.php"); global $DB; $role=$DB->get_record("role",["shortname"=>"sceh_system_admin"],"id",MUST_EXIST); $ctx=context_system::instance(); $caps=["moodle/cohort:view","mod/attendance:view","local/kirkpatrick_dashboard:view","local/sceh_rules:managerules","moodle/badges:viewbadges","moodle/site:config"]; foreach($caps as $cap){ assign_capability($cap, CAP_ALLOW, $role->id, $ctx->id, true); echo "ALLOW\t{$cap}\n"; }'
docker exec -u 1:1 moodlehq-dev-moodle-1 php /var/www/html/public/admin/cli/purge_caches.php
```

## Recreate Mock Users Idempotently

Use this if the environment is reset and users are missing:

```bash
docker exec moodlehq-dev-moodle-1 php -r 'define("CLI_SCRIPT", true); require("/var/www/html/public/config.php"); require_once($CFG->dirroot . "/user/lib.php"); require_once($CFG->libdir . "/moodlelib.php"); require_once($CFG->libdir . "/accesslib.php"); global $DB; $sysctx=context_system::instance(); $users=[["username"=>"mock.sysadmin","firstname"=>"Mock","lastname"=>"System Admin","email"=>"mock.sysadmin@example.local","role"=>"sceh_system_admin"],["username"=>"mock.programowner","firstname"=>"Mock","lastname"=>"Program Owner","email"=>"mock.programowner@example.local","role"=>"sceh_program_owner"],["username"=>"mock.trainer","firstname"=>"Mock","lastname"=>"Trainer","email"=>"mock.trainer@example.local","role"=>"sceh_trainer"],["username"=>"mock.learner","firstname"=>"Mock","lastname"=>"Learner","email"=>"mock.learner@example.local","role"=>null]]; foreach($users as $spec){ $user=$DB->get_record("user",["username"=>$spec["username"],"deleted"=>0],"id,username",IGNORE_MISSING); if(!$user){ $password=bin2hex(random_bytes(12))."Aa1!"; $new=create_user_record($spec["username"],$password,"manual"); $update=(object)["id"=>$new->id,"firstname"=>$spec["firstname"],"lastname"=>$spec["lastname"],"email"=>$spec["email"],"forcepasswordchange"=>1,"confirmed"=>1,"country"=>"US"]; user_update_user($update,false,false); $user=$DB->get_record("user",["id"=>$new->id],"id,username",MUST_EXIST); echo "CREATED\t{$spec["username"]}\n"; } if(!empty($spec["role"])){ $role=$DB->get_record("role",["shortname"=>$spec["role"]],"id,shortname",MUST_EXIST); if(!user_has_role_assignment($user->id,$role->id,$sysctx->id)){ role_assign($role->id,$user->id,$sysctx->id); echo "ASSIGNED\t{$spec["username"]}\t{$spec["role"]}\n"; } } }'
```
