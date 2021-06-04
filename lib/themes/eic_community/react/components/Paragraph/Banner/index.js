import React from 'react';

class Banner extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    return (
      <div className="ecl-editable-hero-banner">
        {this.props.image && <figure className="ecl-editable-hero-banner__image-wrapper">
          <img alt="hero-image" className="ecl-editable-hero-banner__image" src={this.props.image}/>
        </figure>}
        <div className="ecl-editable-hero-banner__main-wrapper">
          <div className="ecl-editable-hero-banner__push"/>
          <div className="ecl-editable-hero-banner__main">
            <div className="ecl-editable-hero-banner__content-wrapper">
              <div className="ecl-editable-hero-banner__content">
                <div className="contextual-region fragment">

                  <div><h2>{this.props.title}</h2></div>

                  <p>{this.props.body}</p>

                  <div><a href={this.props.linkUrl} target="_blank" className="ecl-link ecl-link--default ecl-link--cta">{this.props.linkLabel}</a>
                  </div>

                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

    );
  }
}

export default Banner;
