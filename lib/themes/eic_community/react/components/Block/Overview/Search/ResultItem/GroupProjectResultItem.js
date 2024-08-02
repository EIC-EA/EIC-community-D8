import React from 'react';

const svg = require('../../../../../svg/svg');
import {timeDifferenceFromNow} from '../../../../../Services/TimeHelper';
import UserImage from '../../../../Utils/UserImage';
import {url} from "../../../../../Services/UrlHelper";

class GroupProjectResultItem extends React.Component {
  constructor(props) {
    super(props);
  }


  render() {
    if (!this.props.result) {
      return;
    }

    const moderationState = this.props.result.ss_global_last_moderation_state;
    const itemUrl = url(this.props.result.ss_url);

    let tags = !this.props.isAnonymous ?
      [
        {
          label: this.props.result.ss_group_visibility_label,
          id: this.props.result.ss_group_visibility_label && this.props.result.ss_group_visibility_label.toLowerCase(),
        },
      ] : [];

    if (moderationState !== 'published') {
      let moderationId = 'public';
      switch(moderationState) {
        case 'draft':
        case 'blocked':
          moderationId = 'private';
          break;
        case 'pending':
          moderationId = 'restricted';
          break;
      }

      tags = [...tags, {
        label: moderationState.charAt(0).toUpperCase() + moderationState.slice(1),
        id: moderationId,
      }];
    }

    return (
      <div className="ecl-teaser-overview__item" key={this.props.result.tm_global_title}>
        <div className="ecl-teaser ecl-teaser--group ecl-teaser--project">

          <div className="ecl-teaser__main-wrapper">
            <div className="ecl-teaser__meta-header">
              {tags.map((tag) => <div className={"ecl-editorial-header__tag"}><span className={`ecl-tag ecl-tag--display ecl-tag--is-${tag.id}`}>{tag.label}</span></div>)}
            </div>
            <div className="ecl-teaser__content-wrapper">
              <div className="ecl-teaser__content">
                <h2 className="ecl-teaser__title">
                  <a href={itemUrl}>
                    <span className="ecl-teaser__title-overflow">
                      <span>{this.props.result.tm_global_title}</span>
                    </span>
                  </a>
                </h2>

                <div className="ecl-teaser__meta-header">Meta header</div>
                <div className="ecl-teaser__body">Body</div>
              </div>
            </div>
            <div className="ecl-teaser__meta-footer">
              <div className="ecl-teaser__participating-countries">Participating countries</div>
              <div className="ecl-teaser__programme">Programme:</div>
            </div>
          </div>
        </div>
      </div>
    );
  }
}

export default GroupProjectResultItem;
