let stars = [];
const numStars = 300;

function setup() {
    createCanvas(window.innerWidth, window.innerHeight);
    
    let attempts = 0;
    while (stars.length < numStars && attempts < 3000) {
        let newStar = new Star();
        let overlapping = false;
        for (let star of stars) {
            if (dist(newStar.x, newStar.y, star.x, star.y) < newStar.size + star.size + 8) {
                overlapping = true;
                break;
            }
        }
        if (!overlapping) {
            stars.push(newStar);
        }
        attempts++;
    }
}

function draw() {
    background(180, 0, 0);
    
    let mouseMoving = (mouseX !== pmouseX || mouseY !== pmouseY);
    
    for (let star of stars) {
        star.update(mouseMoving);
        star.display();
    }
}

function mouseMoved() {
    for (let star of stars) {
        let d = dist(mouseX, mouseY, star.x, star.y);
        let maxDist = 200;
        
        if (d < maxDist) {
            let angle = atan2(star.y - mouseY, star.x - mouseX);
            let force = (maxDist - d) / maxDist * 0.8;
            
            star.vx += cos(angle) * force;
            star.vy += sin(angle) * force;
        }
    }
}

function windowResized() {
    resizeCanvas(window.innerWidth, window.innerHeight);
}

class Star {
    constructor() {
        this.homeX = random(30, width - 30);
        this.homeY = random(30, height - 30);
        this.x = this.homeX;
        this.y = this.homeY;
        this.vx = 0;
        this.vy = 0;
        this.size = random(8, 60);
        this.twinkleSpeed = random(0.02, 0.08);
        this.offset = random(TWO_PI);
        this.baseAlpha = random(180, 255);
        this.rotation = random(TWO_PI);
        this.hypnoticOffset = random(TWO_PI);
        this.hypnoticSpeed = random(0.03, 0.08);
    }

    update(mouseMoving) {
        let homeForceX = (this.homeX - this.x) * 0.008;
        let homeForceY = (this.homeY - this.y) * 0.008;
        
        this.vx += homeForceX;
        this.vy += homeForceY;
        
        this.vx *= 0.94;
        this.vy *= 0.94;
        
        this.x += this.vx;
        this.y += this.vy;
        
        this.alpha = this.baseAlpha + sin(frameCount * this.twinkleSpeed + this.offset) * 60;
        
        this.hypnoticOffset += this.hypnoticSpeed;
    }

    display() {
        push();
        translate(this.x, this.y);
        rotate(this.rotation);
        
        let layers = 4;
        for (let layer = layers; layer >= 0; layer--) {
            let layerRatio = layer / layers;
            let expandAmount = sin(this.hypnoticOffset + layer * 0.8) * 0.3;
            let currentSize = this.size * (layerRatio + expandAmount * 0.5);
            
            let alpha = 255;
            
            // 榛戣壊澶栬竟妗?
            fill(0);
            noStroke();
            beginShape();
            for (let i = 0; i < 5; i++) {
                let angle = TWO_PI / 5 * i - HALF_PI;
                let x = cos(angle) * currentSize;
                let y = sin(angle) * currentSize;
                vertex(x, y);
                let innerAngle = angle + TWO_PI / 10;
                let innerX = cos(innerAngle) * (currentSize * 0.45);
                let innerY = sin(innerAngle) * (currentSize * 0.45);
                vertex(innerX, innerY);
            }
            endShape(CLOSE);
            
            // 榛戣壊鏄熸槦
            if (layer === 0) {
                fill(0);
            } else {
                fill(80);
            }
            beginShape();
            for (let i = 0; i < 5; i++) {
                let angle = TWO_PI / 5 * i - HALF_PI;
                let x = cos(angle) * (currentSize * 0.85);
                let y = sin(angle) * (currentSize * 0.85);
                vertex(x, y);
                let innerAngle = angle + TWO_PI / 10;
                let innerX = cos(innerAngle) * (currentSize * 0.38);
                let innerY = sin(innerAngle) * (currentSize * 0.38);
                vertex(innerX, innerY);
            }
            endShape(CLOSE);
        }
        pop();
    }
}
