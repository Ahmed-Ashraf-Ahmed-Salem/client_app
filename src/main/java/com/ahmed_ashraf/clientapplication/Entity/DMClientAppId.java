package com.ahmed_ashraf.clientapplication.Entity;

import jakarta.persistence.Column;
import jakarta.persistence.Embeddable;
import jakarta.persistence.Id;
import lombok.AllArgsConstructor;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;

import java.io.Serializable;

@Embeddable
@Getter
@Setter
@NoArgsConstructor
@AllArgsConstructor
public class DMClientAppId implements Serializable {
    @Column(name = "SERIAL")
    private Long serial;

    @Column(name = "NATIONALNO")
    private String nationalNo;
}
