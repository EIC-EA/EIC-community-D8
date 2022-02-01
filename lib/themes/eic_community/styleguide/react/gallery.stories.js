import React from "react"
import ReactDOMServer from 'react-dom/server';
import Gallery from "@theme/react/components/Block/Gallery/Gallery";

import files from '@theme/data/gallery.data';

export const Slider = () => ReactDOMServer.renderToString(React.createElement(Gallery, {files}));

export default {
  title: 'React / Gallery',
  /* parameters: {
    docs: {
      page: docs,
    },
  }, */
};
