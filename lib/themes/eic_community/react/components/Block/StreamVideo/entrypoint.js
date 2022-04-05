import React from 'react';
import ReactDOM from 'react-dom';
import StreamVideo from './index';

const element = document.getElementById('react-stream-video-block');
const video = 'sample'; // TODO = change by "element.dataset.videoTitle" when the video title is ok in BE

ReactDOM.render(<StreamVideo video={video} />, element);
