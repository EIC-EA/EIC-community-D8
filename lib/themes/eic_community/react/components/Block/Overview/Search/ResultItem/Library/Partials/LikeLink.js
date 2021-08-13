import React from 'react';

class LikeLink extends React.Component {
  constructor(props) {
    super(props);
    this.likeContent = this.likeContent.bind(this);
  }

  likeContent() {
    //@todo
  }

  //@todo wait for like endpoint
  render() {
    return '';
    // return (<div className="ecl-teaser__like">
    //   <a href="#">
    //     <svg className="ecl-icon ecl-icon--xs ecl-teaser__like-icon" focusable="false" aria-hidden="true">
    //     </svg>
    //     {this.props.translations.like}
    //   </a>
    // </div>);
  }
}

export default LikeLink;
