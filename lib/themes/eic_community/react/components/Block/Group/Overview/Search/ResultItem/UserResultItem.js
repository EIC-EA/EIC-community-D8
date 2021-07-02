import React from 'react';
const svg = require('../../../../../../../react/svg/svg')

class UserResultItem extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    if (!this.props.result) {
      return
    }

    return (
      <div className="ecl-teaser-overview__item " key={this.props.result.ss_group_label}>
        <div className="ecl-teaser ecl-teaser--member">
          <div className="ecl-teaser__main-wrapper">
            <div className="ecl-teaser__meta-header ecl-teaser__meta-header--is-hidden" aria-hidden="true">
              <div className="ecl-content-type-indicator ecl-teaser__type">
                <svg className="ecl-icon ecl-icon--s ecl-content-type-indicator__icon" focusable="false"
                     aria-hidden="true">
                </svg>
                <span className="ecl-content-type-indicator__label">&nbsp;</span>
              </div>
            </div>

            <figure className="ecl-teaser__image-wrapper">
              <img className="ecl-teaser__image" src={this.props.result.ss_user_profile_image} alt=""/>
            </figure>

            <div className="ecl-teaser__content">
              <h2 className="ecl-teaser__title">
                <a href={this.props.result.ss_url}>

                  <span
                    className="ecl-teaser__title-overflow"><span>{`${this.props.result.ss_user_last_name} ${this.props.result.ss_user_first_name}`}</span></span>

                </a>
              </h2>

              <p className="ecl-teaser__description">Technical Director</p>
              <div className="ecl-teaser__links">
                <a className="ecl-teaser__link" href="?organisation=boxface_inc">Boxface Inc.</a>
              </div>

              <address className="ecl-teaser__description">Antwerpen, België</address>
            </div>

            <div className="ecl-teaser__meta-footer">
              <div className="ecl-teaser__actions">
                <div className="ecl-teaser__action">


                  <a href="mailto:?john_delaware@example.com"
                     className="ecl-link ecl-link--standalone ecl-link--icon ecl-link--icon-before ecl-link--action ecl-teaser__action-icon ecl-teaser__action-icon-mail">
                    <div dangerouslySetInnerHTML={{__html: svg('mail', 'ecl-icon--m')}} />
                    &nbsp;<span className="ecl-link__label">Contact by E-mail</span></a></div>
                <div className="ecl-teaser__action">
                  <a href="?path=facebook"
                     className="ecl-link ecl-link--standalone ecl-link--icon ecl-link--icon-before ecl-link--action ecl-teaser__action-icon ecl-teaser__action-icon-facebook">
                    <div dangerouslySetInnerHTML={{__html: svg('facebook', 'ecl-icon--m')}} />
                    &nbsp;<span className="ecl-link__label">View facebook profile</span></a></div>
                <div className="ecl-teaser__action">
                  <a href="?path=facebook"
                     className="ecl-link ecl-link--standalone ecl-link--icon ecl-link--icon-before ecl-link--action ecl-teaser__action-icon ecl-teaser__action-icon-twitter">
                    <div dangerouslySetInnerHTML={{__html: svg('twitter', 'ecl-icon--m')}} />
                    &nbsp;<span className="ecl-link__label">View Twitter profile</span></a></div>
                <div className="ecl-teaser__action">
                  <a href="?path=linkedin"
                     className="ecl-link ecl-link--standalone ecl-link--icon ecl-link--icon-before ecl-link--action ecl-teaser__action-icon ecl-teaser__action-icon-linkedin">
                    <div dangerouslySetInnerHTML={{__html: svg('linkedin', 'ecl-icon--m')}} />
                    &nbsp;<span className="ecl-link__label">View LinkedIn page</span></a></div>
              </div>
            </div>
          </div>
        </div>

      </div>
    );
  }
}

export default UserResultItem;
