import React from 'react';

const svg = require('../../../../../../svg/svg')

const StatisticsFooterResult = ({result, enableViews = true, enableMembers = false, children}) => {

  return <div className="ecl-teaser__stats">
    {result?.its_group_statistic_members !== undefined && <div className="ecl-teaser__stat">
      <div dangerouslySetInnerHTML={{__html: svg('group', 'ecl-icon--xs ecl-teaser__stat-icon')}}/>
      <span className="ecl-teaser__stat-label">Members</span>
      <span className="ecl-teaser__stat-value">{result.its_group_statistic_members || 0}</span>
    </div>}
    {result?.ss_content_type !== 'document' || result?.its_content_comment_count !== undefined && <div className="ecl-teaser__stat">
      <div dangerouslySetInnerHTML={{__html: svg('comment', 'ecl-icon--xs ecl-teaser__stat-icon')}}/>
      <span className="ecl-teaser__stat-label">Reactions</span>
      <span className="ecl-teaser__stat-value">{result.its_content_comment_count || 0}</span>
    </div>}
    {enableViews && <div className="ecl-teaser__stat">
      <div dangerouslySetInnerHTML={{__html: svg('views', 'ecl-icon--xs ecl-teaser__stat-icon')}}/>
      <span className="ecl-teaser__stat-label">Views</span>
      <span className="ecl-teaser__stat-value">{result.its_statistics_view || 0}</span>
    </div>}
    {result?.its_flag_like_content !== undefined && <div className="ecl-teaser__stat">
      <div dangerouslySetInnerHTML={{__html: svg('like', 'ecl-icon--xs ecl-teaser__stat-icon')}}/>
      <span className="ecl-teaser__stat-label">Likes</span>
      <span className="ecl-teaser__stat-value">{result.its_flag_like_content || 0}</span>
    </div>}
    {result?.its_flag_recommend_group !== undefined && <div className="ecl-teaser__stat">
      <div dangerouslySetInnerHTML={{__html: svg('like', 'ecl-icon--xs ecl-teaser__stat-icon')}}/>
      <span className="ecl-teaser__stat-label">Likes</span>
      <span className="ecl-teaser__stat-value">{result.its_flag_recommend_group || 0}</span>
    </div>}
    {(result?.ss_content_type === 'document' || result?.ss_type === 'document') && <div className="ecl-teaser__stat">
      <div dangerouslySetInnerHTML={{__html: svg('download', 'ecl-icon--xs ecl-teaser__stat-icon')}}/>
      <span className="ecl-teaser__stat-label">Downloads</span>
      <span className="ecl-teaser__stat-value">{result?.its_document_download_total || 0}</span>
    </div>}
    {children}
  </div>
}

export default StatisticsFooterResult;
