import CircularProgress from '@material-ui/core/CircularProgress';
import React, { useEffect, useState } from 'react';
import axios from 'axios';
import videojs from 'video.js';
import 'video.js/dist/video-js.css';

function StreamVideo({ video }) {
  const [isLoading, setIsLoading] = useState(true);
  useEffect(() => {
    axios.get(`/api/vod/cookies?file=${video.split('.')[0]}`).then((resp) => {
      videojs.Vhs.xhr.beforeRequest = function (options) {
        options.uri = `${options.uri}${resp.data.query_string}`;
        return options;
      };
      const player = videojs('video-player');

      player.src({
        src: resp.data.stream,
        type: 'application/x-mpegURL',
        withCredentials: false,
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
            }`}
          >
            {isLoading && (
              <div className="ecl-media-container__loader">
                <CircularProgress className="" size={50} />
              </div>
            )}
            <video
              controls
              id="video-player"
              className="video-js vjs-big-play-centered vjs-theme-forest"
            ></video>
          </div>
        </figure>
      </div>
    </div>
  );
}

export default StreamVideo;
