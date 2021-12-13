import React from 'react';
import HighlightLink from "./Partials/HighlightLink";
import {timeDifferenceFromNow} from "../../../../../../Services/TimeHelper";
import LikeLink from "./Partials/LikeLink";
import StatisticsFooterResult from "../Partials/StatisticsFooterResult";
import Meta from "./Partials/Meta";

const svg = require('../../../../../../svg/svg')

class DocumentLibraryResultItem extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      isHighlighted: this.props.result.its_flag_highlight_content
    };

    this.updateHighlight = this.updateHighlight.bind(this);
  }

  updateHighlight(isHighlighted) {
    this.setState({
      isHighlighted: isHighlighted,
    })
  }

  render() {
    let isHighlighted = this.state.isHighlighted;
    isHighlighted = typeof isHighlighted !== 'undefined' && isHighlighted !== false;
    const topics = this.props.result.sm_content_field_vocab_topics_string;
    const filenames = this.props.result.sm_filename;
    const filetype = filenames.length > 1 ? 'multiple' : filenames[0].split('.')[1]
    const type = {
      'default': 'document',
      'doc': 'document_doc',
      'dwg': 'document_dwg',
      'html': 'document_html',
      'multiple': 'documents',
      'ppt': 'document_ppt',
      'txt': 'document_txt',
      'xls': 'document_xls',
      'zip': 'document_zip',
    }
    return (<div
        className={`ecl-teaser ecl-teaser--filelist ecl-teaser--is-highlightable ${isHighlighted && 'ecl-teaser--is-highlighted'} ecl-teaser-overview__item--library`}>
        <a className={'ecl-teaser--filelist__thumbnail-link'} href={this.props.result.ss_url}>
          <figure
            className="ecl-teaser__image-wrapper"
            dangerouslySetInnerHTML={{__html: svg(type[filetype] || type['default'], ' ecl-icon--l ecl-teaser__image-icon')}}
          />
        </a>
        <div className="ecl-teaser__main-wrapper">
          <div className="ecl-teaser__meta-header">
            <div className="ecl-teaser__meta-column">
              <div className="ecl-content-type-indicator ecl-teaser__type">
                <span className="ecl-content-type-indicator__label">{this.props.translations.label_file}</span>
              </div>
            </div>
          </div>
          <div className="ecl-teaser__content">
            <h2 className="ecl-teaser__title">
              <a href={this.props.result.ss_url}>
                <span className="ecl-teaser__title-overflow"><span>{this.props.result.tm_global_title} {filenames.length > 1 && `(${filenames.length} files in total)`}</span></span>
              </a>
            </h2>
            <div className="ecl-teaser__tags">
              {topics && topics.length > 0 && topics.map((topic) => {
                return (<div key={topic} className="ecl-teaser__tag">
                  <span className="ecl-tag ecl-tag--display">{topic}</span>
                </div>)
              })}
            </div>
            <div className="ecl-teaser__files">
              {filenames && filenames.length > 0 && filenames.join(', ')}
            </div>
            <Meta result={this.props.result} isAnonymous={this.props.isAnonymous} translations={this.props.translations}  />
            <HighlightLink
              updateHighlight={this.updateHighlight}
              isHighlighted={isHighlighted}
              currentGroupId={this.props.currentGroupId}
              nodeId={this.props.result.its_content_nid}
            />
          </div>
        </div>
        <div className="ecl-teaser__meta-footer">
          <LikeLink translations={this.props.translations} />
          <StatisticsFooterResult result={this.props.result} />
        </div>
      </div>
    );
  }
}

export default DocumentLibraryResultItem;
