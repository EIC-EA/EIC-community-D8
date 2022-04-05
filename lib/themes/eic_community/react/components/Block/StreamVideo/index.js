import React, { useEffect } from 'react';
import axios from 'axios';
import videojs from 'video.js';
import 'video.js/dist/video-js.css';

function StreamVideo({ video }) {
  useEffect(() => {
    axios.get(`/api/vod/cookies?file=${video.split('.')[0]}`).then((resp) => {
      const query = resp.data.stream.split('?')[1];
      videojs.Vhs.xhr.beforeRequest = function (options) {
        if (!options.uri.includes(query)) {
          options.uri = `${options.uri}?${query}`;
        }
        return options;
      };
      const player = videojs('video-player');

      player.src({
        src: resp.data.stream,
        type: 'application/x-mpegURL',
        withCredentials: false,
      });
    });
  }, []);

  return (
    <div className="ecl-media-wrapper">
      <div className="ecl-container">
        <figure className="ecl-media-container">
          <div className={`ecl-media-container__media ecl-media-container__media--ratio-16-9`}>
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
