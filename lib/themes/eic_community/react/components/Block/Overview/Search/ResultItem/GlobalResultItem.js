import React from 'react';
const svg = require('../../../../../svg/svg')
import {getMonthByIndex} from "../../../../../Services/TimeHelper";

class GlobalResultItem extends React.Component {
  constructor(props) {
    super(props);

    this.iconsMap = {
      wiki_page: 'wiki',
      document: 'document',
      page: 'content-page',
      news: 'news',
      story: 'story',
      group: 'group',
      discussion: 'discussion',
      comment: 'comment',
      user: 'user'
    }
  }

  render() {
    if (!this.props.result) {
      return
    }

    const icon = this.iconsMap[this.props.result.ss_global_content_type] || this.iconsMap['page'];
    const date = new Date(this.props.result.ss_global_created_date);

    return (
      <div className="ecl-teaser-overview__item ">

        <div className="ecl-teaser ecl-teaser--search">
          <div className="ecl-teaser__main-wrapper">
            <div className="ecl-teaser__content">
              <h2 className="ecl-teaser__title">
                <a href={this.props.result.ss_url}>

                  <span className="ecl-teaser__title-overflow"><span>{this.props.result.tm_global_title}</span></span>

                </a>
              </h2>

              <div className="ecl-teaser__details">
                <div className="ecl-teaser__detail">
                  <span dangerouslySetInnerHTML={{__html: svg(icon, {class: 'ecl-icon ecl-icon--s ecl-teaser__detail-icon'})}} />
                  <a href="?author=amFuZWRvZQ==" className="ecl-teaser__detail-contributor">{this.props.result.ss_global_fullname} </a>
                  created a new <span className="ecl-teaser__detail-type">{this.props.result.ss_global_content_type_label.replace(/_/g, " ").toLowerCase()}</span>
                  &nbsp;- <div className="ecl-timestamp ecl-timestamp--inherits-color ecl-teaser__timestamp">
                  <time className="ecl-timestamp__label">{`${date.getDate()} ${getMonthByIndex(date.getMonth())} ${date.getFullYear()}`}</time>
                </div>
                  {this.props.result.ss_global_group_parent_label && <div className="ecl-teaser__detail-parentgroup">Found in group: <a href={this.props.result.ss_global_group_parent_url}>{this.props.result.ss_global_group_parent_label}</a></div>}
                </div>
              </div>

              <div className="ecl-teaser__description" dangerouslySetInnerHTML={{__html: this.props.result.tm_X3b_en_rendered_item}} />
            </div>

            <div className="ecl-teaser__meta-footer">
              <div className="ecl-teaser__stats">

                <div className="ecl-teaser__stat">
                  <div dangerouslySetInnerHTML={{__html: svg('comment', 'ecl-icon--xs ecl-teaser__stat-icon')}}/>
                  <span className="ecl-teaser__stat-label">Reactions</span>
                  <span className="ecl-teaser__stat-value">{this.props.result.its_content_comment_count || 0}</span>
                </div>
                <div className="ecl-teaser__stat">
                  <div dangerouslySetInnerHTML={{__html: svg('views', 'ecl-icon--xs ecl-teaser__stat-icon')}}/>
                  <span className="ecl-teaser__stat-label">Views</span>
                  <span className="ecl-teaser__stat-value">{this.props.result.its_statistics_view || 0}</span>
                </div>
                <div className="ecl-teaser__stat">
                  <div dangerouslySetInnerHTML={{__html: svg('like', 'ecl-icon--xs ecl-teaser__stat-icon')}}/>
                  <span className="ecl-teaser__stat-label">Likes</span>
                  <span className="ecl-teaser__stat-value">{this.props.result.its_flag_like_content || 0}</span>
                </div>
                {this.props.result.ss_content_type === 'document' && <div className="ecl-teaser__stat">
                  <div dangerouslySetInnerHTML={{__html: svg('download', 'ecl-icon--xs ecl-teaser__stat-icon')}}/>
                  <span className="ecl-teaser__stat-label">Downloads</span>
                  <span className="ecl-teaser__stat-value">{this.props.result.its_document_download_total || 0}</span>
                </div>}
              </div>
            </div>
          </div>
        </div>

      </div>
    );
  }
}

export default GlobalResultItem;
