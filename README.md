# Capacity planning backend for teams in software development

The application presented here is designed to tackle the challenge of capacity planning within software development
teams. It reduces the workload for planning staff, providing a clear overview of different departments' capacities. The
solution is flexible, ready for extensions and adaptability to other systems such as Scrum.

There is also a frontend which was developed for this calculation software. You can find it here: [capacity-planning-frontend](https://github.com/lorenzpfei/capacity-planning-frontend)


### Laravel

This [Laravel](https://github.com/laravel/laravel) application calculates the capacities of teams in software
development and makes them available via REST.
For login, oAuth via [Socialite](https://github.com/laravel/socialite) is used. This is also used to integrate task and
time tracking system providers.


### Authentication

The authentication is done via [Sanctum](https://github.com/laravel/sanctum). Authentication via OAuth is then available
via the web route. If a user opens this route, they are redirected to the selected platform. After login, the provider
redirects to the callback address, where Laravel then verifies the success of the login, sets the session accordingly,
and uses it to log the person in.

The session is issued to a wildcard URL. Thus, the session can be accessed from the same domain, all directories and all
subdomains. Separation of the domain between front-end and back-end is thus not natively possible.


### Providers

In the application, the strategy pattern was implemented. The user requests the capacities for a department via the API.
The WorkloadService then communicates with the provider set via the Environment file, which are specified via the
respective interfaces. The concrete provider classes can be exchanged as desired and the WorkloadService can continue to
perform correct calculations without adaptation.
To add a new provider for tasks or tracking, create a new class in the corresponding directory
in [/app/Services/](./app/Services) and implement the interface.


## Getting started

1. Start your mariadb database.
2. Create your Environment File `cp .env.example .env` and configure your data.
3. Install all project dependencies via `composer install`.
5. Run the database migrations `php artisan migrate`.
6. Have users register by logging in through the oAuth endpoint.
7. Import the tasks of the users by running `php artisan import:tasks [userId]`. If the tracking data can not be imported directly via the tasks provider, import the tracking data by running `php artisan import:trackings`.
8. After that, import the time offs (vacation, sickness...) running `php artisan import:timeoffs`.
9. Start the app running `php artisan serve` or [deploy](https://laravel.com/docs/10.x/deployment]) your app (Probably you have to overwrite `bootstrap/cache/config.php` again in production).


## Development

If you want to contribute to the project, you are welcome to do so. Known suggestions for improvement or bugs can be found in [Issues](https://github.com/lorenzpfei/capacity-planning-backend/issues).
