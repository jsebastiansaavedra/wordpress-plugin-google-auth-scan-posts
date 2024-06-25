# Coding Task Questions #

# 1. Reducing npm Build File Size:
While executing npm’s build command, you will notice that the resulting zipped file is considerably large. Any suggestions on how to optimize and reduce its size?

If we are ready to take this to production and we know that our code is optimized, we don't have unused code or dependencies and our assets are optimized aswell we can consider different options that Webpack offer us in this case I can consider two options "Code Splitting" Or "Tree Shaking". But as I mentioned before if the plugin is brand new and we consider we don't have dead or unused code, Tree Shaking is not the best option because that will help us with that kind of code. So we have a perfect feature for this situation, Code Splitting, this feature allows us to split the code into bundles that can be loaded on demand or in parallel, reducing the size of the zipped file and providing a good resource management with an excellent optimization. First of all we have to create new modules different than main.jsx, as many as we consider is good for our particular application and then using the webpack.config.js file we have to add them in the entry variable allowing us to specify if each bundle depends on any other and considering it aswell in the output variable and of course avoiding duplication of modules. There are different ways to implement "Code Splitting" we can interact with chunks or preloading modules for example, but this option I present is a pretty basic one that can allow us to reduce the size of the modules.

# 2. Enhancing Google Auth Plugin
The plugin introduces a new admin menu named **Google Auth**, featuring fields for Client ID and Client Secret. To enhance this functionality:

1. Ensure the page is translatable.
2. Set the Client Secret field as a password input for enhanced security.
3. Add functionality to the save button, directing inputs to the `wp-json/wpmudev/v1/auth/auth-url` REST endpoint.
4. Implement notifications for successful storage or error responses.
5. Secure the existing endpoint.
6. Complete the endpoint's callback for storing inputs in the `wpmudev_plugin_test_settings` option.
7. Verify correct retrieval using the mentioned methods.

Page is translatable, Client Secret is a password input, saves pointing to the REST endpoint `wp-json/wpmudev/v1/auth/auth-url`. Has error and succesful messages. The endpoint is secured verifying if the user is logged in and if the user has the right permissions. The endpoint is showing the settings stored.


# 3. Google oAuth Return URL Setup
To implement Google’s oAuth, establish a return URL endpoint at `/wp-json/wpmudev/v1/auth/confirm`, providing functionality to:

1. Retrieve user email.
2. If the email exists and the user is not logged in, log in the user.
3. If the email doesn’t exist, create a new user with a generated password, and log them in. Redirect to the admin or home page accordingly.
4. Create a shortcode to display a personalized message if the user is logged in or a link for Google oAuth login if not.

Endpoint created and shortcode included in plugin's main file. Functionality included in "class-auth.php" where the endpoint was created. after hit "Login with Google" in the post with the shortcode you will be redirected to Google's Auth confirmation and you will be handled as mentioned, if email exists and not logged in you will be logged in, if you email is not registered you wwill be registered and redirected after that. Shortcode is being displayed in this post "http://localhost/wordpress_website_test/shortcode-testing/".


# 4. Admin Menu for Posts Maintenance
Introduce a new admin menu page titled **Posts Maintenance** featuring a **Scan Posts** button. When clicked, this button should scan all public posts and pages (with customizable post type filters) and update the `wpmudev_test_last_scan` post_meta with the current timestamp. Ensure that operation will keep running if the user leaves that page. This operation should be repeated daily to ensure ongoing maintenance.

Admin menu was added and is configured with the file "app/admin-pages/class-posts-maintenance.php".


# 5. WP-CLI Command for Terminal
For system administrators' convenience, create a WP-CLI command to execute the **Scan Posts** action (which you created in Task #4 above) from the terminal. Include clear instructions for usage and customization.

The command works as "wp scan-posts" and perform the functionality defined on step #4.

The command is registered in "app/admin-pages/class-posts-maintenance.php" that is the class for the whole functionality of step #4 but is linked to his own php functions file inside "cli/command-scan-posts.php" in that file we can define with the function __invoke what we want our command to do when is called, we can add aswell as many other functions we want to perform different actions and the syntax to call them is like this "wp scan-posts 'function_name'".

This command is highly customizable and we can use it for example to perform different types of scan, or scanning a specific post type, etc.


# 6. Composer Package Version Control
Prevent conflicts associated with using common composer packages in WordPress. Implement measures to ensure compatibility and prevent version conflicts with other plugins or themes.

We should add a prefix to our composer dependencies, I'm gonna use php-scoper for that. Running the add-prefix command of php-scoper we can isolate dependencies by prefixing the namespaces of our Composer packages. This helps us to prevent conflicts with other plugins or themes that might be using the same packages but different versions.

This command created a file in the root folder called "scooper.inc.php" which contains the configuration of our prefix. I called the prefix "SafePrefix" and is including the folders ['app', 'core', 'cli', 'src'] but we can set any name and any folders for that. After it we run vendor/bin/php-scoper add-prefix --config=scoper.inc.php --output-dir=build to finish the configuration of the prefix and now our Plugin is safe and will avoid conflicts with other plugin or themes.


# 7. Unit Testing for Scan Posts
Prioritize software testing by initiating unit tests. Specifically, design a unit test to validate the 'Scan Posts' functionality, ensuring it runs without errors and effectively scans post content or any specified criteria.

Unit test is defined with the file "tests/TestScanPostsCommand.php", I was having an error when I kept the structure as "test-scan-posts-command.php" because it wasn't able to find the file but is working with this name convention. This file creates 2 new posts and then calls the handle_scan_posts function that manage the 'Scan Posts' functionality, after that it confirms that the timestamp is not empty and if it was modified recently through a period of time comparison with the time at the moment of execution.

https://prnt.sc/Bfw-CMBwRmjR

The result of the unit test was OK with 6 assertions.

**Please be sure to adhere to WPCS rules in your code for all tasks in this test. Following these rules for consistency and best practices is a priority and of crucial importance.**

We wish you good luck!
