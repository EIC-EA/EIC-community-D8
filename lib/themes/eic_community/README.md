# EIC community Theme

The EIC community theme exposes the ECL twig components using [Storybook](https://storybook.js.org/), allowing them to be used seamlessly within Drupal & Storybook.

In order to build the theme correctly you should install the required packages with [npm](https://npmjs.com):

```shell
$ npm install
```

## 2 | Preparing Drupal Theme & Styleguide assets

The assets should be prepared in order to display the theme correctly and can be started by running:

```shell
$ npm run build
```

This will prepare the required assets which are based on the [Europa Component Library](https://ec.europa.eu/component-library/).

The builder configuration can be adjusted by editing the `./ecl-builder.config.js`. By default, the assets will be written to the `./dist` directory.

#### 2.1 | Stylesheets

The custom stylesheets are defined within the `./sass` directory, the included imports will enable the correct styling for the component library for screen & print viewports.

##### Style schematics

Style schematics are defined within the `./sasss/schema` directory where CSS properties are declared within sections. You can output these values by using the proper Sass function for each schema file:

```scss
.example {
  padding: ecl-layout(
    'gutter',
    'meta'
  ); // Equal to: map-get(map-get($ecl-layout, 'meta'), 'gutter').
}
```

##### Sass Mixins

```scss
// Ouputs the base CSS font-size property with the included breakpoint media query variants from the $ecl-typography schematic.
@mixin ecl-responsive-font($type: string, $breakpoints: SassList);
```

##### Sass Functions

```scss
// Outputs the value of the defined `$property`argument from the selected`$type` within the `$ecl-box-model` schematic.
// The box model schematic should contain properties that could be used within reusable components.
@function ecl-box-model($property: string, $type: string);
```

```scss
// Outputs the value of the defined `$property`argument from the selected`$type` within the `$ecl-typography` schematic.
// The typography schematic should contain properties that could be used within typography specific elements.
@function ecl-typography($property: string, $type: string);
```

```scss
// Outputs the value of the defined `$property`argument from the selected`$type` within the `$ecl-layout` schematic.
// The typography schematic should contain properties that could be used within layout specific template, like the gutter between a sidebar and it's main content.
@function ecl-layout($property: string, $type: string);
```

#### 2.2 | Icons

A custom SVG sprite compiler has been created in order to compile the custom icons together with the external component library: **@ecl/ec-preset-legacy-website**.
The defined icon directories can be configured within the builder configurion `ecl-builder.config.js`.

The custom icons will be generated with the `custom` prefix (this can be adjusted within the configuration).
You need to use this prefix during the include of an ECL icon:

```twig
  {% include '@ecl-twig/ec-component-icon/ecl-icon.html.twig' with {
    icon: {
      size: 's',
      path: 'sprite.svg',
      type: 'custom',
      name: 'user',
    },
  } %}
```

The required polyfill [SVG4everybody](https://github.com/jonathantneal/svg4everybody) will be used in order to display the svg sprites within all supported Browsers.

#### 2.3 | Scripts

The ECL javascript library is required in order to display the interactive components correctly. The ECL builder copies these required files to `./dist/scripts`.

Aditional logic should be defined within the `./js` directory; where the code needs to be wrapped within the Drupal behaviors structure:

```js
(function (ECL, Drupal) {
  Drupal.behaviors.defineHANDLER = {
    attach: function attach() {
      ...
    }
  };
})(ECL, Drupal);
```

The defined Drupal behaviors within `./storybook/preview-head.html` will be automatically called within Storybook.

## 3 | Styleguide setup with Storybook

In order to view the EIC community styleguide you first need to install the required packages.
Then you can generate the [Storybook](https://storybook.js.org/) styleguide by running:

```shell
$ npm run dev
```

Finally you can view the styleguide (http://localhost:6006)

#### 3.1 | Usage of Twig in combination with Drupal & Storybook

The actual Storybook implementation for the EIC community theme only displays the theme specific components, the design is based on the ECL component library. Any information regarding the existing components can be found [here](https://ec.europa.eu/component-library/ec/getting-started/)

The base templates should be defined within the `./patterns` directory that will be loaded by Storybook. There should be a Drupal template for each defined pattern that exists within the default Drupal template directory of the theme: `./templates`.
These Drupal templates should include the actual Twig template that is relative to the `./patterns` directory to ensure the data will be included correctly for the external ECL twig components.

#### 3.2 | About Storybook example data

Storybook example data can be found within the `./data` directory and can be included while defining a Storybook entry.
The data structure matches with the structure of the used ECL twig components. Documentation about the component options can be found within [ECL Twig Styleguide](https://ecl-twig-php.netlify.app/ec).
