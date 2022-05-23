import React from "react";
import ReactDOM from 'react-dom';
import Gallery from "@theme/react/components/Block/Gallery/Gallery";
import Image from "./Image";

document.addEventListener('DOMContentLoaded', () => {
  const element = document.getElementById('ecl-gallery-react');
  const files = JSON.parse(element.dataset.files);

  ReactDOM.render(
    files.files.length > 1 ? <Gallery files={files} /> : <Image file={files.files[0]} />,
    element
  );
});


