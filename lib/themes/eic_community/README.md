#EIC community Theme

## First steps
In order to build the theme correclty you should install the required packages with [npm](https://npmjs.com)

```shell
$ npm install
```

## Preparing Drupal Theme & Styleguide assets
To display the theme correctly for both Drupal and the Styleguide the assets have to be prepared first. After the Node package installation has been completed you can generate the assets by running:

```shell
$ npm run build
```

This will prepare the required assets and generates a custom stylesheet that is based on the [Europa Component Library](https://ec.europa.eu/component-library/)

The builder can be configured by adjusting `./ecl-builder.config.js`. By default, the assets will be written within the `./dist` directory.


### Assets: Stylesheets
The custom stylesheets are defined within the `./sass` directory, the included imports will enable the correct styling for the component library for screen & print viewports.

### Assets: Icons
Icon Sprite already have been processed by the external component library: **@ecl/ec-preset-legacy-website**. The builder has been configured to copy the required images from the component library:

```js
// ./ecl-builder.config.js
...
copy: [
  ...
  { from: path.resolve(nodeModules, '@ecl/ec-preset-legacy-website/dist'), to: path.resolve(outputFolder, 'dist') },
  { from: path.resolve(nodeModules, 'svg4everybody/dist'), patterns: 'svg4everybody.min.js', to: path.resolve(outputFolder, 'dist/scripts') },
],
...
```

The required polyfill [SVG4everybody](https://github.com/jonathantneal/svg4everybody) will be used in order to display the svg sprites within all supported Browsers.

### Assets: Scripts
The ECL javascript library is required in order to display the interactive components correctly. The ECL builder copies these required files to `./dist/scripts`.

Actual logic should be defined within the `./js` directory; where the code needs to be wrapped within the Drupal behaviors structure:

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

## Styleguide setup with Storybook
In order to view the EIC community styleguide you first need to install the required packages:

```shell
$ npm install
```

Then you can generate the [Storybook](https://storybook.js.org/) styleguide by running:

```shell
$ npm run storybook
```

Finally you can view the styleguide (http://localhost:6006)
