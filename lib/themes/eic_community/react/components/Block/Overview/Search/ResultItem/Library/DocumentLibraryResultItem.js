import React from 'react';
import HighlightLink from './Partials/HighlightLink';
import {formatShortDate} from "../../../../../../Services/TimeHelper";
import LikeLink from './Partials/LikeLink';
import StatisticsFooterResult from '../Partials/StatisticsFooterResult';
import Meta from './Partials/Meta';

const svg = require('../../../../../../svg/svg');

class DocumentLibraryResultItem extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      isHighlighted: this.props.result.its_flag_highlight_content,
    };

    this.updateHighlight = this.updateHighlight.bind(this);
  }

  updateHighlight(isHighlighted) {
    this.setState({
      isHighlighted: isHighlighted,
    });
  }

  render() {
    let isHighlighted = this.state.isHighlighted;
    isHighlighted = typeof isHighlighted !== 'undefined' && isHighlighted !== false;
    const topics = this.props.result.sm_content_field_vocab_topics_string;
    const filenames = this.props.result.sm_filename;
    const filetype = filenames && filenames.length > 1 ? 'multiple' : filenames[0].split('.')[1];
    const type = {
      default: 'document',
      doc: 'document_doc',
      dwg: 'document_dwg',
      html: 'document_html',
      multiple: 'documents',
      ppt: 'document_ppt',
      txt: 'document_txt',
      xls: 'document_xls',
      zip: 'document_zip',
      pdf: 'document_pdf',
    };
    return (
      <div className="ecl-teaser-overview__item ecl-teaser-overview__item--library">
        <div
          className={`ecl-teaser ecl-teaser--filelist ecl-teaser--is-highlightable ${
            isHighlighted && 'ecl-teaser--is-highlighted'
          }`}
        >
          <figure className="ecl-teaser__image-wrapper">
            <a
              href={this.props.result.ss_url}
              dangerouslySetInnerHTML={{
                __html: svg(
                  type[filetype] || type['default'],
                  ' ecl-icon--l ecl-teaser__image-icon'
                ),
              }}
            />
          </figure>
          <div className="ecl-teaser__main-wrapper">
            <div className="ecl-teaser__meta-header">
              <div className="ecl-teaser__meta-column">
                <div className="ecl-content-type-indicator ecl-teaser__type">
                  <span className="ecl-content-type-indicator__label">
                    {this.props.translations.label_file}
                  </span>
                </div>
                <span> | </span>
                <div className="ecl-timestamp ">
                  <time className="ecl-timestamp__label">{formatShortDate(this.props.result.ss_drupal_timestamp)}</time>
                </div>
              </div>
            </div>
            <div className="ecl-teaser__content">
              <h2 className="ecl-teaser__title">
                <a href={this.props.result.ss_url}>
                  <span className="ecl-teaser__title-overflow">
                    <span>
                      {this.props.result.tm_global_title}
                      {filenames &&
                        filenames.length > 1 &&
                        ` (${Drupal.t(
                          '@count files in total',
                          { '@count': filenames.length },
                          { context: 'eic_group' }
                        )})`}
                    </span>
                  </span>
                </a>
              </h2>
              <div className="ecl-teaser__tags">
                {topics &&
                  topics.length > 0 &&
                  topics.map((topic) => {
                    return (
                      <div key={topic} className="ecl-teaser__tag">
                        <span className="ecl-tag ecl-tag--display">{topic}</span>
                      </div>
                    );
                  })}
              </div>
              <div className="ecl-teaser__files">
                {filenames && filenames.length > 0 && filenames.join(', ')}
              </div>
              <Meta
                result={this.props.result}
                isAnonymous={this.props.isAnonymous}
                translations={this.props.translations}
              />
              <HighlightLink
                updateHighlight={this.updateHighlight}
                isHighlighted={isHighlighted}
                currentGroupId={this.props.currentGroupId}
                nodeId={this.props.result.its_content_nid}
                translation={this.props.translations}
              />
            </div>
          </div>
          <div className="ecl-teaser__meta-footer">
            <LikeLink
              currentGroupId={this.props.currentGroupId}
              nodeId={this.props.result.its_content_nid}
              translations={this.props.translations}
            />
            <StatisticsFooterResult result={this.props.result} />
          </div>
        </div>
      </div>
    );
  }
}

export default DocumentLibraryResultItem;
