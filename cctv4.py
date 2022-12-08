from ast import Break
from cProfile import run
from concurrent.futures import thread
from multiprocessing.resource_sharer import stop
import os
from datetime import datetime
from pickle import FALSE, TRUE
os.environ["OMP_NUM_THREADS"] = "1"
os.environ["OPENBLAS_NUM_THREADS"] = "1"
os.environ["MKL_NUM_THREADS"] = "1"
os.environ["VECLIB_MAXIMUM_THREADS"] = "1"
os.environ["NUMEXPR_NUM_THREADS"] = "1"
import mysql.connector
from threading import Thread
import sys
sys.path.insert(0, './yolov5')
import argparse
import os
import platform
import shutil
from pathlib import Path
import cv2
import torch
import torch.backends.cudnn as cudnn
from flask import Flask, render_template, Response

from yolov5.models.experimental import attempt_load
from yolov5.utils.downloads import attempt_download
from yolov5.models.common import DetectMultiBackend
from yolov5.utils.datasets import LoadImages, LoadStreams
from yolov5.utils.general import (LOGGER, check_img_size, non_max_suppression, scale_coords, 
                                  check_imshow, xyxy2xywh, increment_path)
from yolov5.utils.torch_utils import select_device, time_sync
from yolov5.utils.plots import Annotator, colors, save_one_box
from deep_sort.utils.parser import get_config
from deep_sort.deep_sort import DeepSort

## email
import smtplib
import ssl
from email.message import EmailMessage
import imghdr

#telegram
import telepot
token = '5782342813:AAEkQNNXJexUi71sHE_Jkw-kJMHQN8iJFrE' # telegram token
receiver_id = [320851322, 664202412] # https://api.telegram.org/bot<TOKEN>/getUpdates
bot = telepot.Bot(token)
t7 = int(datetime.now().hour) + 1
# Flask
app = Flask(__name__)
# sub = cv2.createBackgroundSubtractorMOG2()  # create background subtractor

FILE = Path(__file__).resolve()
ROOT = FILE.parents[0]  # yolov5 deepsort root directory
if str(ROOT) not in sys.path:
    sys.path.append(str(ROOT))  # add ROOT to PATH
ROOT = Path(os.path.relpath(ROOT, Path.cwd()))  # relative
count = 0
t7 = int(datetime.now().minute) + 5
car = 0
truck = 0
person = 0
i = 0
jp = ''
data = []
data1 = []
data2 = []
data3 = []
reset = int(datetime.now().minute) + 7

# buat koneksi Mysql
mysql = mysql.connector.connect(user='admin',
                                password='a1b2c3d4',
                                host='192.168.0.66',
                                database='jalan_toll')

mysqlCursor = mysql.cursor()


@app.route('/')
def index():
    """Video streaming home page."""
    return render_template('index.html')

# @app.route('/data')
# def data():
#     """Video streaming home page."""
#     count = {'count':str(count)}
#     return render_template('index1.html', count=count)


def detect1(opt):
    """Video streaming generator function."""

    out, source2, yolo_model2, deep_sort_model, show_vid, save_vid, save_txt, imgsz, evaluate, half, project, name, exist_ok = \
        opt.output, opt.source2, opt.yolo_model2, opt.deep_sort_model, opt.show_vid, opt.save_vid, \
        opt.save_txt, opt.imgsz, opt.evaluate, opt.half, opt.project, opt.name, opt.exist_ok
    webcam = source2 == '0' or source2.startswith(
        'rtsp') or source2.startswith('http') or source2.endswith('.txt')

    # initialize deepsort
    cfg = get_config()
    cfg.merge_from_file(opt.config_deepsort)
    deepsort = DeepSort(deep_sort_model,
                        max_dist=cfg.DEEPSORT.MAX_DIST,
                        max_iou_distance=cfg.DEEPSORT.MAX_IOU_DISTANCE,
                        max_age=cfg.DEEPSORT.MAX_AGE, n_init=cfg.DEEPSORT.N_INIT, nn_budget=cfg.DEEPSORT.NN_BUDGET,
                        use_cuda=True)

    # Initialize
    device = select_device(opt.device)
    half &= device.type != 'cpu'  # half precision only supported on CUDA

    # The MOT16 evaluation runs multiple inference streams in parallel, each one writing to
    # its own .txt file. Hence, in that case, the output folder is not restored
    if not evaluate:
        if os.path.exists(out):
            pass
            shutil.rmtree(out)  # delete output folder
        os.makedirs(out)  # make new output folder

    # Directories
    save_dir = increment_path(Path(project) / name,
                              exist_ok=exist_ok)  # increment run
    save_dir.mkdir(parents=True, exist_ok=True)  # make dir

    # Load model
    device = select_device(device)
    model = DetectMultiBackend(yolo_model2, device=device, dnn=opt.dnn)
    stride, names, pt, jit, _ = model.stride, model.names, model.pt, model.jit, model.onnx
    imgsz = check_img_size(imgsz, s=stride)  # check image size

    # Half
    # half precision only supported by PyTorch on CUDA
    half &= pt and device.type != 'cpu'
    if pt:
        model.model.half() if half else model.model.float()

    # Set Dataloader
    vid_path, vid_writer = None, None
    # Check if environment supports image displays
    if show_vid:
        show_vid = check_imshow()

    # Dataloader
    if webcam:
        show_vid = check_imshow()
        cudnn.benchmark = True  # set True to speed up constant image size inference
        dataset = LoadStreams(source2, img_size=imgsz,
                              stride=stride, auto=pt and not jit)
        bs = len(dataset)  # batch_size
    else:
        dataset = LoadImages(source2, img_size=imgsz,
                             stride=stride, auto=pt and not jit)
        bs = 1  # batch_size
    vid_path, vid_writer = [None] * bs, [None] * bs

    # Get names and colors
    names = model.module.names if hasattr(model, 'module') else model.names

    # extract what is in between the last '/' and last '.'
    txt_file_name = source2.split('/')[-1].split('.')[0]
    txt_path = str(Path(save_dir)) + '/' + txt_file_name + '.txt'

    if pt and device.type != 'cpu':
        model(torch.zeros(
            1, 3, *imgsz).to(device).type_as(next(model.model.parameters())))  # warmup
    dt, seen = [0.0, 0.0, 0.0, 0.0], 0
    for frame_idx, (path, img, im0s, vid_cap, s) in enumerate(dataset):
        t1 = time_sync()
        img = torch.from_numpy(img).to(device)
        img = img.half() if half else img.float()  # uint8 to fp16/32
        img /= 255.0  # 0 - 255 to 0.0 - 1.0
        if img.ndimension() == 3:
            img = img.unsqueeze(0)
        t2 = time_sync()
        dt[0] += t2 - t1

        # Inference
        visualize = increment_path(
            save_dir / Path(path).stem, mkdir=True) if opt.visualize else False
        pred = model(img, augment=opt.augment, visualize=visualize)
        t3 = time_sync()
        dt[1] += t3 - t2

        # Apply NMS
        pred = non_max_suppression(
            pred, opt.conf_thres, opt.iou_thres, opt.classes, opt.agnostic_nms, max_det=opt.max_det)
        dt[2] += time_sync() - t3
        # Process detections
        for i, det in enumerate(pred):  # detections per image
            seen += 1
            if webcam:  # batch_size >= 1
                p, im0, _ = path[i], im0s[i].copy(), dataset.count
                s += f'{i}: '
            else:
                p, im0, _ = path, im0s.copy(), getattr(dataset, 'frame', 0)

            p = Path(p)  # to Path
            save_path = str(save_dir / p.name)  # im.jpg, vid.mp4, ...
            dir = save_path
            s += '%gx%g ' % img.shape[2:]  # print string

            annotator = Annotator(im0, line_width=2, pil=not ascii)
            w, h = im0.shape[1], im0.shape[0]
            if det is not None and len(det):
                # Rescale boxes from img_size to im0 size
                det[:, :4] = scale_coords(
                    img.shape[2:], det[:, :4], im0.shape).round()

                # Print results
                for c in det[:, -1].unique():
                    n = (det[:, -1] == c).sum()  # detections per class
                    # add to string
                    s += f"{n} {names[int(c)]}{'s' * (n > 1)}, "

                xywhs = xyxy2xywh(det[:, 0:4])
                confs = det[:, 4]
                clss = det[:, 5]

                # pass detections to deepsort
                t4 = time_sync()
                outputs = deepsort.update(
                    xywhs.cpu(), confs.cpu(), clss.cpu(), im0)
                t5 = time_sync()
                dt[3] += t5 - t4

                # draw boxes for visualization
                if len(outputs) > 0:
                    for j, (output, conf) in enumerate(zip(outputs, confs)):

                        bboxes = output[0:4]
                        id = output[4]
                        cls = output[5]

                        # count

                        c = int(cls)  # integer class
                        label = f'{id}'
                        # if names[c] == 'car':
                        #     count_car(bboxes,w,h,id)
                        #     # save_one_box(bboxes, im0, file=save_dir / 'crops' / 'car' / f'{p.stem}.jpg', BGR=True)
                        # if names[c] == 'truck' or names[c] == 'bus' or names[c] == 'train':
                        #     count_truck(bboxes,w,h,id)
                        #     # save_one_box(bboxes, im0, file=save_dir / 'crops' / 'truck' / f'{p.stem}.jpg', BGR=True)
                        if names[c] == 'person' or names[c] == 'motorcycle' or names[c] == 'bicycle':
                            dir2 = save_dir / 'person' / f'{id}.jpg'
                            gambar = f'{id}.jpg'
                            dir3 = save_dir / 'crops' / names[c]
                            l = str(Path(save_dir))
                            simpan=l.lstrip('static')
                            count_pelanggaran(
                                bboxes, w, h, id, dir2, im0, gambar, dir3, simpan)

                        # count_obj(bboxes,w,h,id)
                        annotator.box_label(
                            bboxes, color=colors(c, True))

                        if save_txt:
                            # to MOT format
                            bbox_left = output[0]
                            bbox_top = output[1]
                            bbox_w = output[2] - output[0]
                            bbox_h = output[3] - output[1]
                            # Write MOT compliant results to file
                            with open(txt_path, 'a') as f:
                                f.write(('%g ' * 10 + '\n') % (frame_idx + 1, id, bbox_left,  # MOT format
                                                               bbox_top, bbox_w, bbox_h, -1, -1, -1, -1))

            #     LOGGER.info(
            #         f'{s}Done. YOLO:({t3 - t2:.3f}s), DeepSort:({t5 - t4:.3f}s)')

            # else:
            #     deepsort.increment_ages()
            #     LOGGER.info('No detections')

            # Stream results
            im0 = annotator.result()
            if show_vid:
                global count, car, truck
                color = (0, 255, 0)
                # start_point = (400, 640)
                # end_point = (1300, 640)
                # start_point1 = (600, 550)
                # end_point1 = (1100, 550)
                # cv2.line(im0, start_point, end_point, color, thickness=2)
                # cv2.line(im0, start_point1, end_point1, color, thickness=2)
                thickness = 2
                org = (50, 50)
                org1 = (50, 80)
                org2 = (50, 110)
                org3 = (50, 140)
                font = cv2.FONT_HERSHEY_SIMPLEX
                # cap = cv2.VideoCapture('rtsp://admin:admin123@192.168.22.8:554/live1s3.sdp')
                # fps = 30
                fontScale = 1
                # cv2.putText(im0,'total = ' + str(count), org, font,
                # fontScale, color, thickness, cv2.LINE_AA)
                # cv2.putText(im0,'car = ' + str(car), org1, font,
                # fontScale, color, thickness, cv2.LINE_AA)
                # cv2.putText(im0,'fps = ' + str(fps), org2, font,
                # fontScale, color, thickness, cv2.LINE_AA)
                cv2.putText(im0, 'Pelanggaran = ' + str(person), org, font,
                            fontScale, color, thickness, cv2.LINE_AA)
                # cv2.putText(im0,'person= ' + str(person), org3, font,
                # fontScale, color, thickness, cv2.LINE_AA)
                # cv2.imshow(str(p), im0)
                frame = cv2.imencode('.jpg', im0)[1].tobytes()
                yield (b'--frame\r\n'b'Content-Type: image/jpeg\r\n\r\n' + frame + b'\r\n')
                # if cv2.waitKey(1) == ord('q'):  # q to quit
                #     raise StopIteration

            # Save results (image with detections)
            if save_vid:
                if vid_path != save_path:  # new video
                    vid_path = save_path
                    if isinstance(vid_writer, cv2.VideoWriter):
                        vid_writer.release()  # release previous video writer
                    if vid_cap:  # video
                        fps = vid_cap.get(cv2.CAP_PROP_FPS)
                        w = int(vid_cap.get(cv2.CAP_PROP_FRAME_WIDTH))
                        h = int(vid_cap.get(cv2.CAP_PROP_FRAME_HEIGHT))
                    else:  # stream
                        fps, w, h = 30, im0.shape[1], im0.shape[0]
                        save_path += '.mp4'
                    vid_writer = cv2.VideoWriter(
                        save_path, cv2.VideoWriter_fourcc(*'mp4v'), fps, (w, h))
                vid_writer.write(im0)

    # Print results
    t = tuple(x / seen * 1E3 for x in dt)  # speeds per image
    LOGGER.info(f'Speed: %.1fms pre-process, %.1fms inference, %.1fms NMS, %.1fms deep sort update \
        per image at shape {(1, 3, *imgsz)}' % t)
    if save_txt or save_vid:
        print('Results saved to %s' % save_path)
        if platform == 'win':  # MacOS
            os.system('open ' + save_path)


@app.route('/video_feed')
def video_feed():
    """Video streaming route. Put this in the src attribute of an img tag."""
    with torch.no_grad():
        return Response(detect1(opt),
                        mimetype='multipart/x-mixed-replace; boundary=frame')


def count_pelanggaran(box, w, h, id, dir2, im0, gambar, dir3, simpan):
    global person, data2, jp, t6, t7
    center_coordinates = (
        int(box[0]+(box[2]-box[0])/2), int(box[1]+(box[3]-box[1])/2))
    if int(box[1]+(box[3]-box[1])/2) > (0):
        if id not in data2:
            person += 1
            data2.append(id)
            t6 = int(datetime.now().hour)
            save_one_box(box, im0, file=dir2, BGR=True)
            jp = 'Ada Orang'
            inputpelanggaran(jp, gambar, dir2, simpan)
            
            # Mengirimkan data ke bot telegram
            try : 
                for x in receiver_id:
                    bot.sendPhoto(x, photo=open(dir2, 'rb')) # send message to telegram
                    bot.sendMessage(x, 'Pelanggaran di On Ramp Kaluku Badoa') 
            except :
                pass
            
            if (t7==24):
                t7 = 0
            while t6 == t7:
                t7 += 1            
                person = 0

def inputpelanggaran(jp, gambar, dir2, simpan):
    jp = jp
    lokasi = 'On Ramp Kaluku Badoa'
    with open(dir2, 'rb') as f:
        image_data = f.read()
        image_type = imghdr.what(f.name)
        image_name = f.name
    sql = "INSERT INTO data_pelanggaran(JENIS_PELANGGARAN, WAKTU, GAMBAR, LOKASI, project) VALUES ( '" + \
        jp+"', now(), '"+gambar+"', '"+lokasi+"', '"+simpan+"')"
    print(sql)
    mysqlCursor.execute(sql)
    mysql.commit()


if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('--yolo_model2', nargs='+', type=str,
                        default='yolov5s.pt', help='model.pt path(s)')
    parser.add_argument('--deep_sort_model', type=str, default='osnet_x0_25')
    # file/folder, 0 for webcam
    parser.add_argument('--source2', type=str,
                        default='rtsp://admin:admin123@192.168.22.8:554/live1s3.sdp', help='source')
    parser.add_argument('--output', type=str, default='inference/output',
                        help='output folder')  # output folder
    parser.add_argument('--imgsz', '--img', '--img-size', nargs='+',
                        type=int, default=[480], help='inference size h,w')
    parser.add_argument('--conf-thres2', type=float,
                        default=0.5, help='object confidence threshold')
    parser.add_argument('--iou-thres2', type=float,
                        default=0.5, help='IOU threshold for NMS')
    parser.add_argument('--fourcc', type=str, default='mp4v',
                        help='output video codec (verify ffmpeg support)')
    parser.add_argument('--device', default='',
                        help='cuda device, i.e. 0 or 0,1,2,3 or cpu')
    parser.add_argument('--show-vid', action='store_false',
                        help='display tracking video results')
    parser.add_argument('--save-vid', action='store_true',
                        help='save video tracking results')
    parser.add_argument('--save-crop', action='store_false',
                        help='save cropped prediction boxes')
    parser.add_argument('--save_txt', action='store_true',
                        help='save MOT compliant results to *.txt')
    # class 0 is person, 1 is bycicle, 2 is car... 79 is oven
    parser.add_argument('--classes', nargs='+', type=int,
                        help='filter by class: --classes 0, or --classes 0 1 2 3')
    parser.add_argument('--agnostic-nms', action='store_true',
                        help='class-agnostic NMS')
    parser.add_argument('--augment', action='store_true',
                        help='augmented inference')
    parser.add_argument('--evaluate', action='store_true',
                        help='augmented inference')
    parser.add_argument("--config_deepsort", type=str,
                        default="deep_sort/configs/deep_sort.yaml")
    parser.add_argument("--half", action="store_true",
                        help="use FP16 half-precision inference")
    parser.add_argument('--visualize', action='store_true',
                        help='visualize features')
    parser.add_argument('--max-det', type=int, default=1000,
                        help='maximum detection per image')
    parser.add_argument('--dnn', action='store_true',
                        help='use OpenCV DNN for ONNX inference')
    parser.add_argument('--project', default=ROOT /
                        'runs/track', help='save results to project/name')
    parser.add_argument('--name', default='tiga',
                        help='save results to project/name')
    parser.add_argument('--exist-ok', action='store_true',
                        help='existing project/name ok, do not increment')
    opt = parser.parse_args()
    opt.imgsz *= 2 if len(opt.imgsz) == 1 else 1  # expand
    Thread(app.run(host='0.0.0.0', port=5000, debug=True)).start()
