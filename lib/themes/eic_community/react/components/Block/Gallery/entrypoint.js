import React from "react";
import ReactDOM from 'react-dom';
import Gallery from "@theme/react/components/Block/Gallery/Gallery";

const element = document.getElementById('ecl-gallery-react');
const files = JSON.parse(element.dataset.files);


ReactDOM.render(
  <Gallery files={files} />,
  element
);
