import CircularProgress from '@material-ui/core/CircularProgress';
import React, { useEffect, useRef, useState } from 'react';
import axios from 'axios';
import videojs from 'video.js';
import 'video.js/dist/video-js.css';
require('videojs-contrib-quality-levels');
require('videojs-http-source-selector');
import svg from '../../../svg/svg';

function StreamVideo({ video }) {
  const [isLoading, setIsLoading] = useState(true);
  const [hasError, setHasError] = useState(false);
  useEffect(() => {
    axios.get(`/api/vod/cookies?file=${video.split('.')[0]}`).then((resp) => {
      videojs.Vhs.xhr.beforeRequest = function (options) {
        options.uri = options.uri + resp.data.query_string;
        return options;
      };

      const player = videojs('video-player');

      player.src({
        src: resp.data.stream,
        type: 'application/x-mpegURL',
        withCredentials: false,
      });

      player.httpSourceSelector();

      player.on('error', () => {
        console.log(player.error());
        setHasError(true);
      });

      player.ready(() => {
        setIsLoading(false);
      });
    });
  }, []);

  return (
    <div className="ecl-media-wrapper">
      <div className="ecl-container">
        <figure className="ecl-media-container">
          <div
            className={`ecl-media-container__media ecl-media-container__media--ratio-16-9 ${
              isLoading ? 'ecl-media-container--is-loading' : ''
            } ${hasError ? 'ecl-media-container--has-error' : ''}`}
          >
            {isLoading && (
              <div className="ecl-media-container__loader">
                <CircularProgress className="" size={50} />
              </div>
            )}

            {hasError && (
              <div
                className="ecl-media-container__error"
                dangerouslySetInnerHTML={{
                  __html: `${svg(
                    'information',
                    'ecl-icon--3xl'
                  )}${'We are processing this video. Check back later.'}`,
                }}
              ></div>
            )}

            <div>
              <video
                controls
                id="video-player"
                className="video-js vjs-big-play-centered vjs-theme-forest"
              ></video>
            </div>
          </div>
        </figure>
      </div>
    </div>
  );
}

export default StreamVideo;
