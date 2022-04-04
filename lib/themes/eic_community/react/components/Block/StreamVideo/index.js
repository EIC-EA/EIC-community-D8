import React, { useEffect, useState } from 'react';
import CircularProgress from '@material-ui/core/CircularProgress';
import axios from 'axios';
import videojs from 'video.js';

function StreamVideo() {
  const [isLoading, setIsLoading] = useState(false);
  // const myMediaSource = new MediaSource();
  // const url = URL.createObjectURL(myMediaSource);

  useEffect(() => {
    videojs('video-player');
    // axios.get('/api/vod/cookies?file=' + 'sample').then((resp) => {
    //   axios
    //     .get(
    //       'https://communityd8-vod.test.eismea.eu/streams/sample/AppleHLS1/sample.m3u8?Signature=gq2Cpt~r8dZKlCl1oHqJUr3fKeUrQDvCjjmzR4RsH8nE701RjUo21V14tOlO-sFxgXiQdVKg0iMbGFLOTkb1FsF3EkhdsYnZ-1dveKGbVlcHr40nTCtVvhZaI4C3clHrJclEKrGR~xK6~TUQEyaRvVX7SiKUbh2iGJpTI8BrLM1hSsVdifwpZ3lWylqeMC35hOOQWx6lYRAvRoygk3WNjvgUpNaJ-EvxBz7-VXsFS-eu6F26VALlWFlAbwTNw5nFcTGm4e~Ao6B9Rqclx8TBK6l-S4enD1KrwprYY1CyO32LUaOBoEP~2sDTPsUbLsJDc8jO99Ak-gJyXh77brAnkw__&Key-Pair-Id=KY1M2VJLKDWXE&Policy=eyJTdGF0ZW1lbnQiOlt7IlJlc291cmNlIjoiaHR0cHM6Ly9jb21tdW5pdHlkOC12b2QudGVzdC5laXNtZWEuZXUvc3RyZWFtcy9zYW1wbGUvQXBwbGVITFMxL3NhbXBsZSouKiIsIkNvbmRpdGlvbiI6eyJEYXRlTGVzc1RoYW4iOnsiQVdTOkVwb2NoVGltZSI6MTY0OTA1ODY4MDUzOX19fV19'
    //     )
    //     .then((response) => response.data.arryBuffer())
    //     .then((videoData) => {
    //       const videoSourceBuffer = mediaSource.addSourceBuffer(
    //         'video/mp4; codecs="avc1.42E01E, mp4a.40.2"'
    //       );
    //       setIsLoading(false);
    //       videoSourceBuffer.appendBuffer(videoData);
    //     });
    // });
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
            {isLoading ? (
              <div className="ecl-media-container__loader">
                <CircularProgress className="" size={50} />
              </div>
            ) : (
              <video id="video-player" className="video-js">
                <source
                  src={
                    'https://communityd8-vod.test.eismea.eu/streams/sample/AppleHLS1/sample.m3u8?Signature=gq2Cpt~r8dZKlCl1oHqJUr3fKeUrQDvCjjmzR4RsH8nE701RjUo21V14tOlO-sFxgXiQdVKg0iMbGFLOTkb1FsF3EkhdsYnZ-1dveKGbVlcHr40nTCtVvhZaI4C3clHrJclEKrGR~xK6~TUQEyaRvVX7SiKUbh2iGJpTI8BrLM1hSsVdifwpZ3lWylqeMC35hOOQWx6lYRAvRoygk3WNjvgUpNaJ-EvxBz7-VXsFS-eu6F26VALlWFlAbwTNw5nFcTGm4e~Ao6B9Rqclx8TBK6l-S4enD1KrwprYY1CyO32LUaOBoEP~2sDTPsUbLsJDc8jO99Ak-gJyXh77brAnkw__&Key-Pair-Id=KY1M2VJLKDWXE&Policy=eyJTdGF0ZW1lbnQiOlt7IlJlc291cmNlIjoiaHR0cHM6Ly9jb21tdW5pdHlkOC12b2QudGVzdC5laXNtZWEuZXUvc3RyZWFtcy9zYW1wbGUvQXBwbGVITFMxL3NhbXBsZSouKiIsIkNvbmRpdGlvbiI6eyJEYXRlTGVzc1RoYW4iOnsiQVdTOkVwb2NoVGltZSI6MTY0OTA1ODY4MDUzOX19fV19'
                  }
                  type="application/x-mpegURL"
                ></source>
              </video>
            )}
          </div>
        </figure>
      </div>
    </div>
  );
}

export default StreamVideo;
