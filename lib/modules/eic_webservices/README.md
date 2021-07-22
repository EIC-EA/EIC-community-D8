# EIC Webservices

## SMED Taxonomy import

We are importing taxonomy terms for some of the vocabularies.
This is done with the migrate module and some custom plugins.

The SMED taxonomy webservice uses basic http authentication. The `eic_smed_url` source plugin will take care of that provided that:

- you use `eic_smed_api_authentication` as migration tag in the migration yaml file
- you set the following variables in the `settings.php` file:

  - `$settings['smed_api_taxonomy_username'] = 'theusername';`
  - `$settings['smed_api_taxonomy_password'] = 'thepassword';`

We also need to configure the endpoint, which can change based on the environment.
Therefore we also set it automatically, from the `settings.php` file:
- `$settings['smed_api_taxonomy_endpoint'] = 'https://the-endpoint-url';`
