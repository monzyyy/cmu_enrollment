# CoolFreeze

## How to clone this project (using cli)

1. Go to htdocs by typing this in cli: `cd C:/xampp/htdocs`
2. copy and paste this in the cli: `git clone https://github.com/MarkBorito/coolfreeze.git`
3. Go to the coolfreeze project: `cd coolfreeze`
4. Type this: `code .`

---

## Basic Git Commands

1. `git status` - See which files have been changed
2. `git pull origin main` or `git pull` - To download the latest changes
3. `git add .` - Do this after modifying files, to add to staging area.
4. `git commit -m "Sample Message"` - Save your changes with a message
5. `git push origin main` - Upload your branch to GitHub

### Branch

6. `git branch` - View branches
7. `git switch -c <branch_name>` - Create and switch to a new branch
8. `git switch <branch_name>` switching branch

---

## how to use database/ folder

1. Open xampp app then run apache and mysql
2. Open the xampp shell
3. Go to the coolfreeze project, copy and paste this: `cd htdocs\coolfreeze`
4. To use the query of schema.sql and insert_sample.sql in xampp
5. Copy and paste this in xampp shell: `mysql -u root < database\schema.sql` after that,
6. `mysql -u root < database\insert_sample.sql`

### includes/

- use for reuseable pieces of a webpage

### helpers/

- use for reuseable tools/function for php
