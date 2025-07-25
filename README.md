# Archery Score Recording System

This project is the final submission for **COS20031 – Database Design Project** at Swinburne University. It presents a full-stack web application developed to manage and visualise archery scores and competitions for a local archery club. The system supports both archers and club recorders, providing full functionality for score entry, round tracking, competition management, and performance review.

---

## Project Overview

Target archery involves shooting arrows at varying distances using different target face sizes. Archers participate in defined rounds and competitions governed by standardized rules. This system enables archers to record and review their scores, while recorders manage official competitions and maintain data accuracy in line with Archery Australia’s requirements.

The system handles complex data structures including archer categories (based on age, gender, and equipment), round definitions, equivalent round history, and club championship standings.

---

## Database Structure

The database follows a fully normalized relational schema designed to support archery score recording, round definitions, competition tracking, and championship ranking. The schema includes tables for archers, rounds, scores, competitions, categories, and historical data such as equivalent rounds and personal bests.

Below is the Entity Relationship Diagram (ERD) illustrating the core structure:

![ERD – Archery Score Recording System](images/erd_diagram.png)

## Features

### For Archers

- View personal scores by date, round, or total score
- Filter scores by round type or time period
- Submit new practice scores to a staging table
- View round definitions and their components
- Look up equivalent rounds and personal bests (PB)
- Browse competition results and club championship standings

### For Recorders

- Register new archers, rounds, and competitions
- Approve or reject staged practice scores
- Record competition scores down to individual arrow level
- Assign arrows to specific ends and ranges
- Associate scores with competitions and championships
- Track historical changes in equivalent round mappings

---

## Technologies Used

- **Frontend:** HTML, CSS, JavaScript
- **Backend:** PHP
- **Database:** MySQL
- **Development Stack:** XAMPP / MAMP (for local deployment)

---

## How to Run the Project Locally

To run this system locally, follow these steps:

### 1. Install XAMPP or MAMP

- [Download XAMPP](https://www.apachefriends.org/index.html) (recommended)
- Install and launch the control panel
- Start **Apache** and **MySQL**

### 2. Set Up the Project Directory

- Clone or download this repository:
  
  ```bash
  git clone https://github.com/hteng05/Archery-Recording-System.git
  ```

- Move the project folder into the `htdocs` directory (for XAMPP) or the appropriate web root for MAMP.

### 3. Set Up the Database

- Open **phpMyAdmin** (usually at `http://localhost/phpmyadmin`)
- Create a new database (named `archery_db`)
- Import the SQL schema from the provided `database.sql` file (located in the repository)

### 4. Configure Database Connection

- Open the project’s PHP config file (`settings.php` in `includes` folder)  
- Update the host, username, password, and database name as needed:

  ```php
  $host = "localhost";
  $username = "root";
  $password = "";
  $database = "archery_db";
  ```

### 5. Access the Web Application

- In your browser, go to:

  ```
  http://localhost/Archery-Recording-Sysytem/
  ```

- You can now use the system as either an **archer** or a **recorder**.

---

## Learning Objectives

This project addresses the key learning outcomes of the COS20031 unit:

- Design and normalize a relational database using real-world requirements
- Develop a functioning database-driven application in a team environment
- Translate stakeholder needs into practical data models and interfaces
- Apply ethical, professional, and security considerations in data handling
- Utilize version control and project management tools during development

---

## Contributors

This project was collaboratively developed by:

- **Duong Ha Tien Le (@hteng05)** – responsible for the complete website implementation  
- **Uyen Giang Thai (@gangisgiang)** – responsible for the complete database design
- **Hoa Phat Thai** -  responsible for documentations on Confluence

Each team member contributed to requirements analysis, database design, system implementation, and testing.

---

## License

This repository is provided for academic purposes only. Scoring formats and round definitions are derived from public data published by Archery Australia. The reference can be found in project_brief.pdf file.
