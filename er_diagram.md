```mermaid
erDiagram
    USERS {
        int id PK
        string email
        string password
        enum user_type
        timestamp created_at
        timestamp updated_at
    }

    ADMINS {
        int id PK
        int user_id FK
        string name
        string contact
    }

    STUDENTS {
        int id PK
        int user_id FK
        string name
        string roll_number
        string department
        int year_of_passing
        decimal cgpa
        string resume_path
        string contact
        text about
        text skills
    }

    COMPANIES {
        int id PK
        int user_id FK
        string name
        text description
        string website
        string location
        string contact_person
        string contact_email
        string contact_phone
    }

    JOB_POSTINGS {
        int id PK
        int company_id FK
        string title
        text description
        text requirements
        string location
        enum job_type
        string salary
        date deadline
        timestamp created_at
        timestamp updated_at
    }

    APPLICATIONS {
        int id PK
        int job_id FK
        int student_id FK
        enum status
        timestamp applied_date
        timestamp updated_at
    }

    ANNOUNCEMENTS {
        int id PK
        int admin_id FK
        string title
        text content
        timestamp created_at
    }

    EVENTS {
        int id PK
        string title
        text description
        datetime event_date
        string location
        int created_by FK
        timestamp created_at
    }

    TRAINING_PROGRAMS {
        int id PK
        string title
        text description
        text prerequisites
        text syllabus
        string instructor
        date start_date
        date end_date
        string duration
        string location
        enum status
        timestamp created_at
        timestamp updated_at
    }

    TRAINING_ENROLLMENTS {
        int id PK
        int training_program_id FK
        int student_id FK
        datetime enrollment_date
        enum status
        datetime completion_date
        timestamp created_at
        timestamp updated_at
    }

    USERS ||--o{ ADMINS : "has"
    USERS ||--o{ STUDENTS : "has"
    USERS ||--o{ COMPANIES : "has"
    USERS ||--o{ EVENTS : "creates"

    ADMINS ||--o{ ANNOUNCEMENTS : "creates"

    COMPANIES ||--o{ JOB_POSTINGS : "posts"

    JOB_POSTINGS ||--o{ APPLICATIONS : "receives"
    STUDENTS ||--o{ APPLICATIONS : "submits"

    TRAINING_PROGRAMS ||--o{ TRAINING_ENROLLMENTS : "has"
    STUDENTS ||--o{ TRAINING_ENROLLMENTS : "enrolls_in"
}
```
