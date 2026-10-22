<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>


## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## Researcher + Writer AI

This app runs two AI agents in sequence: the Researcher gathers relevant facts for the user's request, and the Writer responds to that request. It does not assume a social media platform; if the user explicitly asks for a post in a particular style or platform, the Writer creates that format. Choose OpenAI or Gemini on the form.

1. Add the key for each provider you want to use to `.env`. Get a Gemini API key from [Google AI Studio](https://aistudio.google.com/app/apikey):

   ```env
   GEMINI_API_KEY=your-gemini-api-key
   GEMINI_MODEL=gemini-2.5-flash
   OPENAI_API_KEY=your-openai-api-key
   OPENAI_MODEL=gpt-4o-mini
   ```

2. Start the Laravel development server:

   ```bash
   php artisan serve
   ```

3. Open `http://127.0.0.1:8000`, enter a topic, and run both agents.

The application uses OpenAI's PHP client and calls Gemini's generateContent API using Laravel's HTTP client. Keep API credentials in `.env`; do not commit them.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
