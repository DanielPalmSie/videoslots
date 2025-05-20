# Misc-Script-Runner

This tool can be used to execute PHP scripts, used to run miscellaneous database queries, and bash scripts on pre-defined environments.

## Usage Instructions

For the latest doc: [Production Deployment Instructions: Deploying DB misc scripts.](https://wikijs.videoslots.com/en/home/development/prod_deployment_instructions)

1. Create new branch (from main) using GitLab integration plugin, in Jira.

    - Branch name has to be in full, such as this format: `type/PROJECT_KEY-xxx-description`. For example, a story: <https://videoslots.atlassian.net/browse/DEVOPS-1> has full branch name: `feature/DEVOPS-1-test-script-output`. The type field denotes story category, and should always be part of the branch name or else the script might not be able to correctly get the story number, used to post updates to stories automatically.

2. In the branch, we need a folder named with the Story ID. Assuming a Story ID is `123456` and brand is Videoslots:
    - For script related to main-site/phive:
        - `cp -a story_template_phive/ 123456_videoslots`
    - For any other script (mostly for Laravel: admin2 and sportsbook):
        - `cp -a story_template_bash/ 123456_videoslots_admin2`

    - The brand suffix should change according to brand and script type:
        - `kungaslottet` ==> Kungaslottet scripts.
        - `kungaslottet_admin2` ==> Kungaslottet Admin2 scripts.
        - `kungaslottet_sports` ==> Kungaslottet Sportsbook scripts.
        - `mrvegas` ==> MrVegas scripts.
        - `mrvegas_admin2` ==> MrVegas Admin2 scripts.
        - `mrvegas_partnerroom` ==> MrVegas Partnerroom scripts.
        - `videoslots_partnerroom` ==> Videoslots Partnerroom scripts.
        - `mrvegas_sports` ==> MrVegas Sportsbook scripts.
        - `videoslots_sports` ==> Videoslots Sportsbook scripts.
        - `mts` ==> MTS scripts.
        - `videoslots` ==> Videoslots scripts.
        - `videoslots_admin2` ==> Videoslots Admin2 scripts.
        - `videoslots_admin2_melita5` ==> Videoslots Admin2 scripts on Melita5.
        - `videoslots_it` ==> Videoslots scripts in IT folder.
        - `videoslots_melita5` ==> Videoslots scripts on Melita5.
        - `dbet_sports` ==> DBet Sportsbook scripts.
        - `megariches_sports` ==> Megariches Sportsbook scripts.

3. Make changes to `script.php`:
    - For main-site/phive, `script.php` should contain all required code, typically an SQL query. Set options in this script.
    - For bash scripts, also add changes to `bash_script.sh`.
        - Edit `requester` to show story requester.
        - post_shortcut: set `true` to post output to story, false if story outputs sensitive content such as tokens.
        - close_story: set `true` to set story as Done, false to leave story status unchanged.
        - move_story_folder: set `true` to move the completed story folder including output log file to folder `archived`.
        - push_script_output: set `true` to push the completed story folder including output log file to git.
        - is_test: `true` will override and disable the 4 variables above - set `false` for production
        - create_lockfile: set `true` to create a lockfile to avoid script being executed twice, otherwise `false` to ignore the lockfile creation and pushing.
        - Place any required CSV files, with the proper filename, in the story folder.
    - For adding new games to main site, running IT scripts in DiamondBet IT folder:
        - Uncomment this variable in bash_script.sh
        - `$root_folder_path = "{$root_folder_path}/diamondbet/soap/IT";`
    - For `admin2` migration execution without needing a MR, check section `Deploying Migration or Seeder scripts` in [Wiki](https://wikijs.videoslots.com/en/home/development/prod_deployment_instructions).

4. Add new files, commit and push story branch:

    ```bash
    git add .
    git commit -am "[123456] Adding script for the captioned story."
    git push origin <new-branch-name>
    ```

5. Create a merge request merging story-branch to main.

6. For release engineers:
    - In the proper merge request shown in Jira, click `Merge`.
    - Go to the CI/CD => Pipelines and click `Run pipeline`.
    - In the form that shows up, select the required values such as:
    - `STORY_ID` can be left blank, and this value will be obtained from the last merged branch. If another branch was merged, and we need to run another script, please type this story's ID such as `ASE-123`.
    - The required `BRAND` for this script - please refer to brand list above.
    - A field EXTRA_ARGS can contain any extra argument that can be added to the script and supplied via the pipeline.
    - For variables related to migrations, check section `Deploying Migration or Seeder scripts` in [Wiki](https://wikijs.videoslots.com/en/home/development/prod_deployment_instructions).
    - Click `Run pipeline`.
    - Start by clicking `run_script`. Script output will be shown in pipeline.

## Troubleshooting

The pipeline's console output should be observed by the release engineer, to watch out for potential issues. On issues, the script will terminate and error messages shown.

One a script starts, in the respective folder, a file `.lockfile` is created to indicate that the script was executed. This is done to avoid scripts being executed twice.
Should there be a script run that halted with an error, ensure that the lockfile is deleted before retrying.

Script real time output can be viewed on the respective server:

- `tail -f /home/gitlab-runner/misc-script-runner/nohup.out`

Scripts are triggered with `nohup`. Unless there is a syntax or logical error, it will complete irrespective of connectivity loss, or pipeline timeouts.
In such cases, please check file `nohup.out` as noted above, or the file below:

- `tail -f /home/gitlab-runner/misc-script-runner/123456_videoslots/123456_videoslots_output.log`

Check php process:

- `ps -ef | grep "misc.php"`

When a script is completed, the output will be posted to the related story, which will also be set as *Done*.

In the case of scripts having long output, the output log file `<StoryID>_<product>_output.log` can be viewed in the story folder on GitLab.

After successful script execution, the story folder will be moved into folder `archived`, including the content of any script output. The resulting folder is pushed to git.

## Branches

These are the used branches:

- `main`
- `dev-branch` (commit any script updates to this branch).

## New Environment Setup Instructions

For new setup, clone this project to a server having a working Phive or Laravel instance for the needed brand.

```bash
sudo su - gitlab-runner
git clone git@gitlab-ssh.videoslots.com:utilities/misc-script-runner.git
```

Ensure that the bash arrays named `servers`, `root_folder_name`, and `brand_admin2` in file `.gitlab-ci.yml` are complete and cover each brand required.

## Automated Script Execution

Steps:

- Rename file `brand_config.php.example` to `brand_config.php`
- Configure script attributes such as `sc_id`, `brand` and `root_folder_name` according to story type and paths required in the environment.
- Use variable `is_test` with value true.
- Run script with this command: `./misc.php`

## Setting up Misc-Script-Runner for a new brand

Steps:

In file `.gitlab-ci.yml` add the appropriate variables:

- `BRAND` : *brand name in lowercase excluding domain*
- `root_folder_name` : *application folder name*
- `servers` : *variable name containing target environment server hostname*
- `brand_admin2` : *admin2 application folder name*

In project CI/CD settings: add a new environment variable containing target environment server hostname.

---
