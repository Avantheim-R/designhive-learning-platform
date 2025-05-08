
Built by https://www.blackbox.ai

---

# DesignHIve

DesignHIve is an interactive learning platform focused on graphic design education aimed at students from SMK Negeri 3 Bantul. This project offers an engaging learning experience with interactive materials, educational mini-games, and certifications upon course completion.

## Project Overview

DesignHIve provides a comprehensive solution for students to enhance their graphic design skills through dynamic content that simplifies the learning process. It features modern UI/UX designs that make studying more enjoyable and effective.

## Installation

To set up the project on your local machine, follow these steps:

1. **Clone the repository:**
   ```bash
   git clone https://github.com/yourusername/designhive.git
   ```
2. **Navigate to the project directory:**
   ```bash
   cd designhive
   ```
3. **Set up a Local Server:**
   - You can use a local server such as XAMPP, MAMP, or WAMP. Place the `designhive` folder in the `htdocs` directory (for XAMPP).
   
4. **Access the project in your web browser:**
   - Open a web browser and go to `http://localhost/designhive/index.php` (or your local server URL).

## Usage

- **To log in:** Navigate to the Login page (`login.php`) and enter your credentials.
- **To register:** Go to the Registration page (`register.php`) and fill out the form to create a new account.
- **Access Learning Materials:** After logging in, students can explore various educational materials and mini-games designed to enhance their learning experience.

## Features

- **Interactive Materials:** Engaging content that includes text, images, and videos.
- **Educational Mini Games:** Fun and interactive games that reinforce learning concepts.
- **Digital Certification:** Users can earn a certificate upon completion of their courses.
- **User-Friendly Interface:** Easy navigation with a responsive design suitable for all devices.

## Dependencies

This project utilizes the following dependencies:

- [Tailwind CSS](https://tailwindcss.com/) for styling the components.
- [Font Awesome](https://fontawesome.com/) for icons used throughout the application.

These dependencies are linked directly in the HTML files and do not require installation through npm or any other package manager.

## Project Structure

The project contains the following file structure:

```
designhive/
│
├── index.php         # Main landing page of the application
├── login.php         # Login page for users
├── register.php      # Registration page for new users
└── includes/
    └── auth/
        ├── login_process.php    # Script to handle login process
        └── register_process.php  # Script to handle registration process
```

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Contributing

If you'd like to contribute to this project, please fork the repository and submit a pull request. 

## Acknowledgments

Thank you for visiting the DesignHIve project. We hope it helps in your graphic design education journey!