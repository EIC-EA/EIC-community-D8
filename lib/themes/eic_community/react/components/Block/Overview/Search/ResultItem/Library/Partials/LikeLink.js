import React from 'react';
import svg from "../../../../../../../svg/svg";

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
    return (
      <div className="ecl-teaser__like">
        <a href="#">
          <span
            dangerouslySetInnerHTML={{__html: svg('like', 'ecl-icon ecl-icon--xs')}}
          />
          &nbsp;
          {this.props.translations.like}
        </a>
      </div>
    );
  }
}

export default LikeLink;
