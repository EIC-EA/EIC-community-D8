import React from "react";
import ReactDOM from "react-dom";

import Banner from "./index";

const elements = document.getElementsByClassName('hero-paragraph');

Array.from(elements).map(element => ReactDOM.render(
  <Banner
    image={element.dataset.heroBg}
    color={element.dataset.heroBgColor}
    title={element.dataset.heroTitle}
    linkLabel={element.dataset.heroLinkLabel}
    linkUrl={element.dataset.heroLinkUrl}
    body={element.dataset.heroBody}
  />,
  element
));

