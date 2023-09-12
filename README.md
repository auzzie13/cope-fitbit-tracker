# cope-fitbit-tracker

To add a new project to the steps tracker, navigate here: https://dev.fitbit.com/apps (need credentials to the account that owns the app)

Edit the application tied to this module, and add a new line to the Redirect URL in this form: https://redcap.vanderbilt.edu/external_modules/?prefix=health-plus-steps-tracker&page=fitbit_users&NOAUTH=&pid=[pid]
replacing [pid] with the proejct ID.

Now the module can be enabled on the project and the start/end dates can be set.
