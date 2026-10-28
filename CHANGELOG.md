# v0.2.0
##  27/10/2026

1. [](#new)
    * Grav 2.0 compatibility: the "Password Protection" tab now shows up in the new admin (admin2) and passwords set there are hashed on save
1. [](#improved)
    * Declare `compatibility: grav: ['1.7', '2.0']` and depend on Grav instead of the classic admin plugin, which does not exist on Grav 2
1. [](#bugfixes)
    * Fix "Call to undefined method stdClass::undef()" when saving a page whose header is a plain object
    * Fix PHP warning on protected pages without a password hash

# v0.1.2
##  17/02/2021

1. [](#enhancement)
    * Add markdown support for "Description" field

# v0.1.1
##  17/02/2021

1. [](#bugfixes)
    * Fix "onAdminSave" if no "header()" method exists

# v0.1.0
##  08/01/2021

1. [](#new)
    * Initial commit

